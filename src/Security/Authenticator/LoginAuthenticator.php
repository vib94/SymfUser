<?php

namespace Vib\SymfUser\Security\Authenticator;

use Vib\SymfUser\Form\Type\LoginType;
use Vib\SymfUser\Entity\User;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class LoginAuthenticator extends AbstractFormLoginAuthenticator
{
    private $formFactory;
    private $passwordEncoder;
    private $router;
    private $params;
    private $checkRoute;
    private $checkRedirect;

    public function __construct(
        FormFactoryInterface $formFactory, 
        RouterInterface $router,
        UserPasswordEncoderInterface $passwordEncoder,
        ParameterBagInterface $params
    ) 
    {
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->passwordEncoder = $passwordEncoder;
        $this->params = $params;

        $this->checkRoute = $params->has('auth_check_route') ? $params->get('auth_check_route') : 'login';
        $this->checkRedirect = $params->has('auth_check_route_redirect') ? $params->get('auth_check_route_redirect') : 'index';
    }

    public function getCredentials(Request $request)
    {
        $form = $this->formFactory->create(LoginType::class);
        $form->handleRequest($request);

        $data = $form->getData();

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $data['username']
        );

        return $data;
    }
    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        if ($request->get('_route') != $this->checkRoute || $request->getMethod() != 'POST') {
            return false;
        }
        //$request->getPathInfo() != '/admin/login'
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
        //$url = $this->router->generate($this->targetPath);
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
    
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials['username']);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        if (!$this->passwordEncoder->isPasswordValid($user, $credentials['password'])) {
            return false;
        }

        if (!$user->isEnabled()) {
            throw new CustomUserMessageAuthenticationException('Suspended');
        }
/*
        if (!$user->hasRole(User::ADMIN_ROLE)) {
            throw new CustomUserMessageAuthenticationException("You don't have permission to access that page.");
        }
*/
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        $url = $this->router->generate($this->checkRoute);

        return new RedirectResponse($url);
    }

    public function supportsRememberMe()
    {
    }

    protected function getLoginUrl()
    {
        $url = $this->router->generate($this->checkRoute);

        return new RedirectResponse($url);
    }
}
