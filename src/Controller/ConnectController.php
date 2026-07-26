<?php

namespace App\Controller;

use App\Command\Connect\YandexConnectCommand;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class ConnectController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    #[Route('/connect', name: 'connect_index')]
    public function index(): Response
    {
        return $this->render('connect/index.html.twig');
    }

    #[Route('/connect/yandex', name: 'connect_yandex_start')]
    public function connectYandex(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('yandex_main')
            ->redirect(['login:email']); // Запрашиваем email
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/connect/yandex/check', name: 'connect_yandex_check')]
    public function connectYandexCheck(): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new YandexConnectCommand());

            return $this->redirectToRoute('app_index');
        } catch (RuntimeException) {
            return $this->redirectToRoute('connect_index');
        }
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
