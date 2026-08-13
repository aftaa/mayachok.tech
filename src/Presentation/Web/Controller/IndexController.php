<?php

namespace App\Presentation\Web\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    #[Route('/', 'app_index')]
    public function indexAction(): Response
    {
        return $this->redirectToRoute('app_mixes');
    }
}
