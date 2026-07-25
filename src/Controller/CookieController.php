<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

class CookieController extends AbstractController
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) { }


    #[Route('/api/cookies/accept', name: 'api_cookies_accept', methods: ['POST'])]
    public function accept(): JsonResponse
    {
        $this->requestStack->getSession()->set('cookies_accepted', true);
        return $this->json(['success' => true]);
    }
}
