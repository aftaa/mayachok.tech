<?php

namespace App\Factory;

use Aego\OAuth2\Client\Provider\YandexResourceOwner;
use App\Entity\User;
use DateMalformedStringException;
use DateTime;

class UserFactory
{
    const string SUPER_ADMIN_EMAIL = 'after@ya.ru';

    public function fromYandexResourceOwner(YandexResourceOwner $resourceOwner): User
    {
        $owner = $resourceOwner->toArray();
        $avatarUrl = $owner['is_avatar_empty']
            ? ''
            : sprintf('https://avatars.yandex.net/get-yapic/%s/islands-retina-50', $owner['default_avatar_id']);

        try {
            $birthday = new DateTime($owner['birthday']);
        } catch (DateMalformedStringException|\Exception $e) {
            $birthday = null;
        }

        $user = new User();
        $user->setEmail($resourceOwner->getEmail());
        $user->setDisplayName($resourceOwner->getNickname());
        $user->setAvatarUrl($avatarUrl);
        $user->setBirthday($birthday);
        $user->setRoles($this->getRoles($resourceOwner));
        $user->setOauthId($resourceOwner->getId());
        $user->setPassword('');

        if ($this->isSuperAdmin($resourceOwner)) {
            $user->setStorageLimit(0);
        }

        return $user;
    }

    /**
     * @return list<string>
     */
    private function getRoles(YandexResourceOwner $resourceOwner): array
    {
        return $this->isSuperAdmin($resourceOwner) ? ['ROLE_SUPER_ADMIN'] : ['ROLE_USER'];
    }
    private function isSuperAdmin(YandexResourceOwner $resourceOwner): bool
    {
        return self::SUPER_ADMIN_EMAIL === $resourceOwner->getEmail();
    }
}
