<?php

namespace App\Security;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function loginAction(AuthenticationUtils $authenticationUtils): Response
    {
        $userConnected = $this->getUser();
        if ($userConnected) {
          return $this->redirectToRoute('app_homepage');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', array(
            'last_username' => $lastUsername,
            'error'         => $error,
        ));
    }

    #[Route('/login_check', name: 'app_login_check', methods: ['POST'])]
    public function loginCheck()
    {
        // This code is never executed.
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logoutCheck()
    {
        // This code is never executed.
    }
}
