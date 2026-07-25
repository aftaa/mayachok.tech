<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class YandexAuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
    ) {}

    #[Route('/api/auth/yandex', name: 'api_auth_yandex', methods: ['POST'])]
    public function auth(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $displayName = $data['displayName'] ?? 'Пользователь';
        $avatarUrl = $data['avatarUrl'] ?? null;
        $oauthId = $data['yandexId'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email is required'], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setDisplayName($displayName);
            $user->setAvatarUrl($avatarUrl);
            $user->setRoles(['ROLE_USER']);
            $user->setOauthId($oauthId);
            $user->setPassword('');

            $this->userRepository->save($user);
        }

        $this->loginUser($user);

        return $this->json([
            'success' => true,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'displayName' => $user->getDisplayName(),
                'avatarUrl' => $user->getAvatarUrl(),
            ]
        ]);
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
    }
}
