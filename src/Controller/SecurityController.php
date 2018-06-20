<?php

namespace Vib\SymfUser\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Vib\SymfUser\Form\Type\LoginType;

class SecurityController extends Controller
{

    public function login(Request $request, AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        $lastUsername = $authenticationUtils->getLastUsername();

        $form = $this->createForm(LoginType::class, ['email' => $lastUsername]);

        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'form' => $form->createView(),
            'error' => $error,
        ]);
    }

    public function logout()
    {
    }
}
