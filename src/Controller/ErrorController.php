<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ErrorController extends AbstractController
{
    #[Route('/_error/{code}', name: 'app_error', requirements: ['code' => '\d+'])]
    public function show(Request $request, int $code = 500): Response
    {
        $exception = $request->attributes->get('exception');

        if (true || 'debug' === $_ENV['debug'] || $this->isGranted('ROLE_ADMIN') && $code === 500) {
            return $this->render('error/debug.html.twig', [
                'exception' => $exception,
            ]);
        }

        return $this->render('error/500.html.twig', [
            'code' => $code,
        ]);
    }
}
