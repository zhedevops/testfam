<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    public function index(
        AuthenticationUtils $authenticationUtils,
        Request $request
    ): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        $successMessage = $request->query->get('success');
        $request->getSession()->set('logged_user_email', $lastUsername);

        return $this->render('login/index.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'success' => $successMessage ?? ''
        ]);
    }

    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
