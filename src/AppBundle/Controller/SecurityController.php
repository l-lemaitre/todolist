<?php

namespace App\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function loginAction(Request $request)
    {
        /*$authenticationUtils = $this->get('security.authentication_utils');

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();*/

        return $this->render('security/login.html.twig', array(
            'last_username' => '',
            'error'         => '',
        ));
    }

    #[Route('/login_check', name: 'app_login_check')]
    public function loginCheck()
    {
        // This code is never executed.
    }

    #[Route('/logout', name: 'app_logout')]
    public function logoutCheck()
    {
        // This code is never executed.
    }
}
