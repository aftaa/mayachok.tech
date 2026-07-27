<?php

namespace App\Factory;

use Aego\OAuth2\Client\Provider\YandexResourceOwner;
use App\Entity\User;
use DateTime;
use DateTimeImmutable;

class UserFactory
{
    public function fromYandexResourceOwner(YandexResourceOwner $resourceOwner): User
    {
        $owner = $resourceOwner->toArray();
        $avatarUrl = $owner['is_avatar_empty']
            ? ''
            : sprintf('https://avatars.yandex.net/get-yapic/%s/islands-retina-50', $owner['default_avatar_id']);

        try {
            $birthday = new DateTime($owner['birthday']);
        } catch (\DateMalformedStringException|\Exception $e) {
            $birthday = null;
        }

        $user = new User();
        $user->setEmail($resourceOwner->getEmail());
        $user->setDisplayName($resourceOwner->getNickname());
        $user->setAvatarUrl($avatarUrl);
        $user->setBirthday($birthday);
        $user->setRoles(['ROLE_USER']);
        $user->setOauthId($resourceOwner->getId());
        $user->setPassword('');

        return $user;
    }
}
