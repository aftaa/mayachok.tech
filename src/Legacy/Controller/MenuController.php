<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MenuController extends AbstractController
{
    #[Route('/_menu', name: 'app_menu')]
    public function menu(Request $request): Response
    {
        return $this->render('_menu.html.twig', ['rand' => $request->query->get('rand', 423)]);
    }
}
