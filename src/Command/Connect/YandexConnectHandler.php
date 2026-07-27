<?php

namespace App\Command\Connect;

use Aego\OAuth2\Client\Provider\YandexResourceOwner;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsMessageHandler(bus: 'command.bus')]
readonly class YandexConnectHandler
{
    public function __construct(
        private UserRepository           $userRepository,
        private TokenStorageInterface    $tokenStorage,
        private RequestStack             $requestStack,
        private UserFactory              $userFactory,
        private ClientRegistry           $clientRegistry,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface          $logger,
    )
    {
    }

    public function __invoke(YandexConnectCommand $command): void
    {
        $this->logger->info('🚀 YandexConnectHandler called');

        try {
            $client = $this->clientRegistry->getClient('yandex_main');

            /** @var YandexResourceOwner $yandexUser */
            $yandexUser = $client->fetchUser();
            $this->logger->info('✅ Yandex user fetched', ['email' => $yandexUser->getEmail()]);

            $user = $this->userRepository->findOneBy(['email' => $yandexUser->getEmail()]);

            if (null === $user) {
                $this->logger->info('🆕 Creating new user');
                $user = $this->userFactory->fromYandexResourceOwner($yandexUser);
                $this->userRepository->save($user);
            } else {
                $this->logger->info('👤 Existing user found', ['id' => $user->getId()]);
            }

            $this->loginUser($user);
            $this->logger->info('✅ User logged in successfully');
        } catch (IdentityProviderException $e) {
            $this->logger->error('❌ IdentityProviderException: ' . $e->getMessage());
            throw new RuntimeException('Ошибка авторизации через Яндекс', 0, $e);
        } catch (\Throwable $e) {
            $this->logger->error('❌ Unexpected error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new RuntimeException('Неожиданная ошибка: ' . $e->getMessage(), 0, $e);
        }
    }

    private function loginUser(User $user): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            $this->logger->error('❌ No request in loginUser');
            throw new RuntimeException('No request available');
        }

        $this->logger->info('🔐 Creating security token');

        // Создаем токен с правильным firewall
        $token = new UsernamePasswordToken(
            $user,
            'main', // Название вашего файрвола из security.yaml
            $user->getRoles()
        );

        // Сохраняем токен в хранилище
        $this->tokenStorage->setToken($token);
        $this->logger->info('✅ Token stored');

        // Диспатчим событие входа
        $event = new InteractiveLoginEvent($request, $token);
        $this->eventDispatcher->dispatch($event);
        $this->logger->info('✅ Login event dispatched');
    }
}
