<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Mix\Entity\Mix;
use App\Domain\Mix\Repository\MixRepositoryInterface;
use App\Domain\Mix\Specification\SpecificationInterface;
use App\Domain\Mix\ValueObject\MixId;
use App\Infrastructure\Doctrine\Entity\Mix as MixDoctrine;
use App\Infrastructure\Mix\Doctrine\Repository\ArtistName;
use App\Infrastructure\Mix\Doctrine\Repository\DateTime;
use App\Infrastructure\Mix\Doctrine\Repository\Duration;
use App\Infrastructure\Mix\Doctrine\Repository\FileId;
use App\Infrastructure\Mix\Doctrine\Repository\FileSize;
use App\Infrastructure\Mix\Doctrine\Repository\MixStatus;
use App\Infrastructure\Mix\Doctrine\Repository\MixTitle;
use App\Infrastructure\Mix\Doctrine\Repository\TrackMetadata;
use App\Infrastructure\Mix\Doctrine\Repository\UserId;
use App\Infrastructure\Mix\Doctrine\Repository\Visibility;
use App\Infrastructure\Mix\Doctrine\Specification\SpecificationAdapterFactory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MixRepository extends ServiceEntityRepository implements MixRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly SpecificationAdapterFactory $adapterFactory,
    ) {
        parent::__construct($registry, MixDoctrine::class);
    }

    // ========================================
    // 1. БАЗОВЫЕ ОПЕРАЦИИ
    // ========================================

    public function save(Mix $mix): void
    {
        $entity = $this->toDoctrineEntity($mix);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function delete(Mix $mix): void
    {
        $entity = $this->findDoctrineById($mix->getId());
        if ($entity !== null) {
            $this->getEntityManager()->remove($entity);
            $this->getEntityManager()->flush();
        }
    }

    // ========================================
    // 2. ПОИСК
    // ========================================

    public function findById(MixId $id): ?Mix
    {
        $entity = $this->findDoctrineById($id);
        return $entity !== null ? $this->toDomainEntity($entity) : null;
    }

    public function findByUuid(string $uuid): ?Mix
    {
        $entity = $this->findOneBy(['uuid' => $uuid]);
        return $entity !== null ? $this->toDomainEntity($entity) : null;
    }

    /**
     * @return Mix[]
     */
    public function findMatches(SpecificationInterface $specification): array
    {
        $adapter = $this->adapterFactory->create($specification);
        $qb = $this->createQueryBuilder('m');

        // Добавляем JOIN-ы
        foreach ($adapter->getJoins() as $join) {
            $qb->leftJoin($join, null, 'WITH');
        }

        // Добавляем WHERE
        $dql = $adapter->toDQL('m');
        if ($dql !== null) {
            $qb->where($dql);
        }

        // Добавляем параметры
        foreach ($adapter->getParameters() as $key => $value) {
            $qb->setParameter($key, $value);
        }

        $results = $qb->getQuery()->getResult();

        return array_map(
            fn(MixDoctrine $entity) => $this->toDomainEntity($entity),
            $results
        );
    }

    public function countMatches(SpecificationInterface $specification): int
    {
        $adapter = $this->adapterFactory->create($specification);
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)');

        // Добавляем JOIN-ы
        foreach ($adapter->getJoins() as $join) {
            $qb->leftJoin($join, null, 'WITH');
        }

        // Добавляем WHERE
        $dql = $adapter->toDQL('m');
        if ($dql !== null) {
            $qb->where($dql);
        }

        // Добавляем параметры
        foreach ($adapter->getParameters() as $key => $value) {
            $qb->setParameter($key, $value);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function exists(MixId $id): bool
    {
        $entity = $this->findDoctrineById($id);
        return $entity !== null;
    }

    // ========================================
    // 3. ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ (ПРИВАТНЫЕ)
    // ========================================

    private function findDoctrineById(MixId $id): ?MixDoctrine
    {
        return $this->findOneBy(['uuid' => $id->toString()]);
    }

    // ========================================
    // 4. КОНВЕРТАЦИЯ DOMAIN ↔ DOCTRINE
    // ========================================

    private function toDoctrineEntity(Mix $mix): MixDoctrine
    {
        $entity = new MixDoctrine();

        // Базовые свойства
        $entity->setUuid($mix->getId()->toString());
        $entity->setTitle($mix->getMetadata()->getTitle()->toString());
        $entity->setArtist($mix->getMetadata()->getArtist()->toString());
        $entity->setIsPrivate($mix->isPrivate());

        // Статус
        $entity->setStatus($mix->getStatus()->value);
        $entity->setIsProcessed($mix->isReady());

        // Файлы
        if ($mix->getOriginalFileId() !== null) {
            $entity->setOriginalPath($mix->getOriginalFileId()->toString());
            $entity->setS3OriginalKey($mix->getOriginalFileId()->toString());
        }

        if ($mix->getStreamFileId() !== null) {
            $entity->setS3StreamKey($mix->getStreamFileId()->toString());
        }

        if ($mix->getPeaksFileId() !== null) {
            $entity->setPeaksKey($mix->getPeaksFileId()->toString());
        }

        // Размеры
        if ($mix->getOriginalFileSize() !== null) {
            $entity->setOriginalSize($mix->getOriginalFileSize()->toBytes());
        }

        if ($mix->getStreamFileSize() !== null) {
            $entity->setMp3Size($mix->getStreamFileSize()->toBytes());
        }

        if ($mix->getPeaksFileSize() !== null) {
            $entity->setPeaksSize($mix->getPeaksFileSize()->toBytes());
        }

        // Длительность
        if ($mix->getDuration() !== null) {
            $entity->setDuration($mix->getDuration()->toSeconds());
        }

        // Даты
        $entity->setCreatedAt($mix->getCreatedAt()->toDateTimeImmutable());

        if ($mix->getProcessedAt() !== null) {
            $entity->setProcessedAt($mix->getProcessedAt()->toDateTimeImmutable());
        }

        // TODO: Связь с пользователем
        // $user = $this->userRepository->findOneBy(['id' => $mix->getOwnerId()->toInt()]);
        // $entity->setUser($user);

        return $entity;
    }

    private function toDomainEntity(MixDoctrine $entity): Mix
    {
        // Собираем все параметры для restore()
        $id = MixId::fromString($entity->getUuid());

        $metadata = new TrackMetadata(
            new MixTitle($entity->getTitle()),
            new ArtistName($entity->getArtist())
        );

        // TODO: Получить UserId из связи
        $ownerId = UserId::fromInt($entity->getUser()->getId());

        $visibility = $entity->isPrivate()
            ? Visibility::private()
            : Visibility::public();

        $status = match($entity->getStatus()) {
            'pending' => MixStatus::PENDING,
            'processing' => MixStatus::PROCESSING,
            'ready' => MixStatus::READY,
            'failed' => MixStatus::FAILED,
            default => MixStatus::PENDING,
        };

        $originalFileId = $entity->getS3OriginalKey() !== null
            ? FileId::fromString($entity->getS3OriginalKey())
            : null;

        $streamFileId = $entity->getS3StreamKey() !== null
            ? FileId::fromString($entity->getS3StreamKey())
            : null;

        $peaksFileId = $entity->getPeaksKey() !== null
            ? FileId::fromString($entity->getPeaksKey())
            : null;

        $originalFileSize = $entity->getOriginalSize() !== null
            ? new FileSize($entity->getOriginalSize())
            : null;

        $streamFileSize = $entity->getMp3Size() !== null
            ? new FileSize($entity->getMp3Size())
            : null;

        $peaksFileSize = $entity->getPeaksSize() !== null
            ? new FileSize($entity->getPeaksSize())
            : null;

        $duration = $entity->getDuration() !== null
            ? new Duration($entity->getDuration())
            : null;

        $createdAt = DateTime::fromDateTimeImmutable($entity->getCreatedAt());

        $processedAt = $entity->getProcessedAt() !== null
            ? DateTime::fromDateTimeImmutable($entity->getProcessedAt())
            : null;

        // Восстанавливаем микс
        return Mix::restore(
            $id,
            $metadata,
            $ownerId,
            $visibility,
            $status,
            $originalFileId,
            $streamFileId,
            $peaksFileId,
            $originalFileSize,
            $streamFileSize,
            $peaksFileSize,
            $duration,
            $createdAt,
            $processedAt,
        );
    }
}
