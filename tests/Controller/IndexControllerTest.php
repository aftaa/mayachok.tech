<?php

namespace App\Tests\Controller;

use App\Entity\Mix;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IndexControllerTest extends WebTestCase
{
    public function testIndexShowsOnlyPublicMixes(): void
    {
        $client = static::createClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // Создаем пользователя
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setDisplayName('Test User');
        $user->setOauthId('123');
        $user->setPassword('');
        $entityManager->persist($user);

        // Публичный микс
        $publicMix = new Mix();
        $publicMix->setTitle('Public Mix');
        $publicMix->setArtist('Test Artist');
        $publicMix->setOriginalPath('/var/uploads/public.mp3');
        $publicMix->setUser($user);
        $publicMix->setIsProcessed(true);
        $publicMix->setIsPrivate(false);
        $entityManager->persist($publicMix);

        // Приватный микс
        $privateMix = new Mix();
        $privateMix->setTitle('Private Mix');
        $privateMix->setArtist('Test Artist');
        $privateMix->setOriginalPath('/var/uploads/private.mp3');
        $privateMix->setUser($user);
        $privateMix->setIsProcessed(true);
        $privateMix->setIsPrivate(true);
        $entityManager->persist($privateMix);

        $entityManager->flush();

        // Запрос к главной
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('html', 'Public Mix');
        $this->assertSelectorTextNotContains('html', 'Private Mix');
    }
}
