<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConnectController extends AbstractController
{
    #[Route('/connect', name: 'app_connect')]
    public function connect(): Response
    {
        return $this->render('connect/yandex.html.twig');
    }

    #[Route('/auth/yandex/callback', name: 'app_yandex_auth_callback')]
    public function yandexAuthCallback(): Response
    {
        return $this->render('connect/empty.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
