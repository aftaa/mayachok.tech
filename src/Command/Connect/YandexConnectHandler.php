<?php

namespace App\Command\Connect;

use Aego\OAuth2\Client\Provider\YandexResourceOwner;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Repository\UserRepository;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[AsMessageHandler(bus: 'command.bus')]
readonly class YandexConnectHandler
{
    public function __construct(
        private UserRepository        $userRepository,
        private TokenStorageInterface $tokenStorage,
        private RequestStack          $requestStack,
        private UserFactory           $userFactory,
        private ClientRegistry        $clientRegistry
    ) {
    }

    public function __invoke(YandexConnectCommand $command): void
    {
        try {
            $client = $this->clientRegistry->getClient('yandex_main');

            /** @var YandexResourceOwner $yandexUser */
            $yandexUser = $client->fetchUser();

            $user = $this->userRepository->findOneBy(['email' => $yandexUser->getEmail()]);

            if (null === $user) {
                $user = $this->userFactory->fromYandexResourceOwner($yandexUser);
                $this->userRepository->save($user);
            }

            $this->loginUser($user);
        } catch (IdentityProviderException $e) {
            throw new RuntimeException(previous: $e);
        }
    }

    private function loginUser(User $user): void
    {
        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );

        $this->tokenStorage->setToken($token);
        $this->requestStack->getSession()->set('_security_main', serialize($token));
        $this->requestStack->getSession()->save();
    }

}
