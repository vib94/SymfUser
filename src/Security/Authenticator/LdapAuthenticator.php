<?php
namespace Vib\SymfUser\Security\Authenticator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Ldap\Ldap;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Vib\SymfUser\Form\Type\LoginType;
use Vib\SymfUser\Entity\User;

class LdapAuthenticator extends AbstractGuardAuthenticator
{

    private $em;
    
    private $ldapHost;
    private $ldapDomain;
    private $ldapPort;
    private $ldapDn;
    private $ldapPassword;
    private $checkPath;
    private $ldapBaseSearch;
    private $ldapUsernameField;
    private $ldapFilter;
    private $ldapFilterConnect;
    private $userCreateProvider;
    private $targetPath;
    private $loginPath;

    private $router;
    private $params;

    public function __construct(
        FormFactoryInterface $formFactory, 
        RouterInterface $router,
        EntityManager $em,
        ParameterBagInterface $params
    )
    {
        $this->em = $em;
        $this->router = $router;
        $this->params = $params;
        
        $this->checkRoute = $params->has('auth_check_route') ? $params->get('auth_check_route') : 'login';
        $this->checkRedirect = $params->has('auth_check_route_redirect') ? $params->get('auth_check_route_redirect') : 'index';
        $this->ldapHost = $params->has('auth_ldap_host') ? $params->get('auth_ldap_host') : 'localhost';
        $this->ldapUsernameField = $params->has('auth_ldap_username_field') ? $params->get('auth_ldap_username_field') : 'mail';
        $this->ldapDomain = $params->has('auth_ldap_domain') ? $params->get('auth_ldap_domain') : null;
        $this->ldapPort = $params->has('auth_ldap_port') ? $params->get('auth_ldap_port') : 389;
        $this->ldapDn = $params->has('auth_ldap_dn') ? $params->get('auth_ldap_dn') : '';
        $this->ldapPassword = $params->has('auth_ldap_password') ? $params->get('auth_ldap_password') : '';
        $this->ldapBaseSearch = $params->has('auth_ldap_basesearch') ? $params->get('auth_ldap_basesearch') : '';
        $this->ldapFilter = $params->has('auth_ldap_filter') ? $params->get('auth_ldap_filter') : '';
        $this->ldapFilterConnect = $params->has('auth_ldap_filterConnect') ? $params->get('auth_ldap_filterConnect') : '';
        $this->checkPath =  $params->has('auth_ldap_connect_check') ? $params->get('auth_ldap_connect_check') : 'login';;
        $this->loginPath = array_key_exists('loginPath', $conf) ? $conf['loginPath'] : 'fos_user_security_login';
        $this->targetPath = array_key_exists('targetPath', $conf) ? $conf['targetPath'] : 'homepage';
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        if ($request->get('_route') != $this->checkRoute)
        {
            return false;
        }
        return true;
    }    
    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser(). Returning null will cause this authenticator
     * to be skipped.
     */
    public function getCredentials(Request $request)
    {
        $connect_string = $this->ldapHost.':'.$this->ldapPort;

        $un = $request->get('_username');
        $pass = $request->get('_password');

        $token = array();
        try 
        {

            $ldap = Ldap::create('ext_ldap', array('connection_string' => $connect_string));

            $dn = $un;
            if(!is_null($this->ldapDomain) && !empty($this->ldapDomain))
            {
                $dn .= '@'.$this->ldapDomain;
            }
            
            $password = $pass;
            $ldap->bind($dn, $password);

            $query = $ldap->query($this->ldapBaseSearch, str_replace('$un', $un, $this->ldapFilterConnect), array('filter' => '*', 'sizeLimit' => 10 ));
            $results = $query->execute();
        
            $token['attributes'] = array();

            foreach ($results as $entry) 
            {
                $cn = $entry->getAttribute('cn');
                $token['attributes']['cn'] = $cn;
                $displayn = $entry->getAttribute('displayName');
                $token['attributes']['displayName'] = $displayn[0];
                $mail = $entry->getAttribute('mail');
                $token['attributes']['mail'] = $mail[0];
                break;
            }

        } catch (ConnectionException $e) 
        {
            return false;
        }

        $token['username'] = $un;
        $token['password'] = $pass;
        //$token['attributes']  = $attributes;
        $token['created']  = date('Y-m-d H:i:s');


        // What you return here will be passed to getUser() as $credentials
        return array(
            'token' => $token,
        );
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $token = $credentials['token'];

        // if null, authentication will fail
        if (!isset($token['username']) || is_null($token['username'])) {
            return;
        }

        // if null, authentication will fail
        if (!isset($token['password']) || is_null($token['password'])) {
            return;
        }
        try 
        {
            $user = $userProvider->loadUserByUsername($token['username']);               
        } 
        catch (UsernameNotFoundException $e)
        {
            $attributes = $token['attributes'];

            $un = array_key_exists($this->ldapUsernameField, $attributes) ? trim($attributes[$this->ldapUsernameField]) : null;
            $random = random_bytes(10);
            $user = new user();
            $user->setUsername($un);
            $user->setPlainPassword($random);
            $manager->persist($user);
            $manager->flush();
            
            //if (!$user = $this->userCreateProvider->createUser($token, $userProvider)) {
            //    throw new UsernameNotFoundException();
            //}

        }
        // if a User object, checkCredentials() is called
        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case

        // return true to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {

/*
        if (null === $jwt) {
            $jwt = $this->jwtManager->create($user);
        }

        $response = new JWTAuthenticationSuccessResponse($jwt);
        $event    = new AuthenticationSuccessEvent(['token' => $jwt], $user, $response);

        $this->dispatcher->dispatch(Events::AUTHENTICATION_SUCCESS, $event);
        $response->setData($event->getData());

        return $response;
*/
        $url = $this->router->generate($this->checkRedirect);
        $response = new RedirectResponse($url);
        /*
        $usernametoswitch = $request->query->get('_switch_user');
        if (!empty($usernametoswitch)) {

            $roles[] = new SwitchUserRole('ROLE_PREVIOUS_ADMIN', $token);

            $token = array();
            $token['username'] = $usernametoswitch;
            $token['attributes']  = array();
            $token['created']  = date('Y-m-d H:i:s');

            return array(
                'token' => $token,
            );           
        }*/
        // on success, let the request continue
        return $response;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $url = $this->router->generate($this->checkRedirect);
        $response = new RedirectResponse($url);
        
        //$request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        //$url = $this->router->generate($this->targetPathFailure);
        //$response = new RedirectResponse($url);
        
        //return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        if($request->getRequestUri() != $this->router->generate($this->auth_check_route))
        {
            $url = $this->router->generate($this->auth_check_route);
            $response = new RedirectResponse($url);            
            return $response;
        }

        return;
    }

    public function supportsRememberMe()
    {
        return false;
    }
}