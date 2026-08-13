<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Mapper;

use App\Domain\Mix\Entity\Mix;
use App\Domain\Mix\ValueObject\ArtistName;
use App\Domain\Mix\ValueObject\Duration;
use App\Domain\Mix\ValueObject\FileId;
use App\Domain\Mix\ValueObject\FileSize;
use App\Domain\Mix\ValueObject\MixId;
use App\Domain\Mix\ValueObject\MixStatus;
use App\Domain\Mix\ValueObject\MixTitle;
use App\Domain\Mix\ValueObject\TrackMetadata;
use App\Domain\Mix\ValueObject\Visibility;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\Doctrine\Entity\Mix as MixDoctrine;  // 👈 ПРАВИЛЬНЫЙ ПУТЬ!
use App\Infrastructure\Doctrine\Entity\User as UserDoctrine; // 👈 ДЛЯ СВЯЗИ С ПОЛЬЗОВАТЕЛЕМ
use App\Shared\Domain\ValueObject\DateTime;

final class MixMapper implements MixMapperInterface  // 👈 НУЖЕН ИНТЕРФЕЙС
{
    public function toDoctrine(Mix $mix): MixDoctrine
    {
        $entity = new MixDoctrine();

        // Базовые свойства
        $entity->setUuid($mix->getId()->toString());
        $entity->setTitle($mix->getMetadata()->getTitle()->toString());
        $entity->setArtist($mix->getMetadata()->getArtist()->toString());
        $entity->setIsPrivate($mix->isPrivate());
        $entity->setStatus($mix->getStatus()->value);
        $entity->setIsProcessed($mix->isReady());

        // Файлы
        $entity->setOriginalPath($mix->getOriginalFileId()?->toString());
        $entity->setS3OriginalKey($mix->getOriginalFileId()?->toString());
        $entity->setS3StreamKey($mix->getStreamFileId()?->toString());
        $entity->setPeaksKey($mix->getPeaksFileId()?->toString());

        // Размеры
        $entity->setOriginalSize($mix->getOriginalFileSize()?->toBytes());
        $entity->setMp3Size($mix->getStreamFileSize()?->toBytes());
        $entity->setPeaksSize($mix->getPeaksFileSize()?->toBytes());

        // Длительность
        $entity->setDuration($mix->getDuration()?->toSeconds());

        // Даты
        $entity->setCreatedAt($mix->getCreatedAt()->toDateTimeImmutable());
        $entity->setProcessedAt($mix->getProcessedAt()?->toDateTimeImmutable());

        // TODO: Связь с пользователем
        // $entity->setUser($this->getUserDoctrine($mix->getOwnerId()));

        return $entity;
    }

    public function toDomain(MixDoctrine $entity): Mix
    {
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

    // 👇 ВСПОМОГАТЕЛЬНЫЙ МЕТОД ДЛЯ ПОЛУЧЕНИЯ ПОЛЬЗОВАТЕЛЯ
    private function getUserDoctrine(UserId $userId): UserDoctrine
    {
        // TODO: Реализовать поиск пользователя
        // Это временное решение, лучше использовать UserRepository
        throw new \RuntimeException('Not implemented yet');
    }
}
