<?php

namespace App\Tests\Repository;

use App\Entity\Mix;
use App\Entity\User;
use App\Repository\MixRepository;
use App\Specification\PublicMixesSpecification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MixRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private MixRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Mix::class);

        // Начинаем транзакцию, чтобы откатить изменения после теста
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
        parent::tearDown();
    }

    public function testFindPublicReturnsOnlyPublicMixes(): void
    {
        // Создаем пользователя
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setDisplayName('Test User');
        $user->setOauthId('123');
        $user->setPassword('');
        $this->entityManager->persist($user);

        // Публичный микс
        $publicMix = new Mix();
        $publicMix->setTitle('Public Mix');
        $publicMix->setArtist('Test Artist');
        $publicMix->setOriginalPath('/var/uploads/public.mp3');
        $publicMix->setUser($user);
        $publicMix->setIsProcessed(true);
        $publicMix->setIsPrivate(false);
        $this->entityManager->persist($publicMix);

        // Приватный микс
        $privateMix = new Mix();
        $privateMix->setTitle('Private Mix');
        $privateMix->setArtist('Test Artist');
        $privateMix->setOriginalPath('/var/uploads/private.mp3');
        $privateMix->setUser($user);
        $privateMix->setIsProcessed(true);
        $privateMix->setIsPrivate(true);
        $this->entityManager->persist($privateMix);

        $this->entityManager->flush();

        // Тестируем
        $result = $this->repository->findMatches(new PublicMixesSpecification());

        $this->assertCount(1, $result);
        $this->assertSame('Public Mix', $result[0]->getTitle());
    }
}
