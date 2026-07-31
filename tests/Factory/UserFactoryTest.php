<?php

namespace App\Tests\Factory;

use Aego\OAuth2\Client\Provider\YandexResourceOwner;
use App\Factory\UserFactory;
use PHPUnit\Framework\TestCase;

class UserFactoryTest extends TestCase
{
    public function testFromYandexResourceOwner(): void
    {
        // Создаем мок YandexResourceOwner
        $resourceOwner = $this->createMock(YandexResourceOwner::class);
        $resourceOwner->method('getEmail')->willReturn('test@yandex.ru');
        $resourceOwner->method('getNickname')->willReturn('TestUser');
        $resourceOwner->method('getId')->willReturn('12345');
        $resourceOwner->method('toArray')->willReturn([
            'is_avatar_empty' => false,
            'default_avatar_id' => '12345',
            'birthday' => '1990-01-01',
        ]);

        $factory = new UserFactory();
        $user = $factory->fromYandexResourceOwner($resourceOwner);

        $this->assertSame('test@yandex.ru', $user->getEmail());
        $this->assertSame('TestUser', $user->getDisplayName());
        $this->assertSame('12345', $user->getOauthId());
        $this->assertSame(['ROLE_USER'], $user->getRoles());
        $this->assertStringContainsString('avatars.yandex.net', $user->getAvatarUrl());
        $this->assertNotNull($user->getBirthday());
    }
}
