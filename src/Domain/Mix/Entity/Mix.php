<?php

declare(strict_types=1);

namespace App\Domain\Mix\Entity;

use App\Domain\Mix\Event\AnalysisCompleted;
use App\Domain\Mix\Event\ConversionCompleted;
use App\Domain\Mix\Event\MixCompleted;
use App\Domain\Mix\Event\MixCreated;
use App\Domain\Mix\Event\MixMadePrivate;
use App\Domain\Mix\Event\MixMadePublic;
use App\Domain\Mix\Event\MixProcessingFailed;
use App\Domain\Mix\Event\MixProcessingStarted;
use App\Domain\Mix\Exception\InvalidTransitionException;
use App\Domain\Mix\Exception\NoOriginalFileException;
use App\Domain\Mix\ValueObject\Duration;
use App\Domain\Mix\ValueObject\FileId;
use App\Domain\Mix\ValueObject\FileSize;
use App\Domain\Mix\ValueObject\MixId;
use App\Domain\Mix\ValueObject\MixStatus;
use App\Domain\Mix\ValueObject\TotalSize;
use App\Domain\Mix\ValueObject\TrackMetadata;
use App\Domain\Mix\ValueObject\Visibility;
use App\Domain\User\ValueObject\UserId;
use App\Shared\Domain\ValueObject\DateTime;

final class Mix
{
    private MixId $id;
    private TrackMetadata $metadata;
    private UserId $ownerId;
    private MixStatus $status;
    private Visibility $visibility;
    private ?FileId $originalFileId = null;
    private ?FileId $streamFileId = null;
    private ?FileId $peaksFileId = null;
    private ?FileSize $originalFileSize = null;
    private ?FileSize $streamFileSize = null;
    private ?FileSize $peaksFileSize = null;
    private ?Duration $duration = null;
    private DateTime $createdAt;
    private ?DateTime $processedAt = null;

    /** @var array<object> */
    private array $events = [];

    // ========================================
    // 1. КОНСТРУКТОР И ФАБРИКИ
    // ========================================

    private function __construct(
        MixId $id,
        TrackMetadata $metadata,
        UserId $ownerId,
        Visibility $visibility,
    ) {
        $this->id = $id;
        $this->metadata = $metadata;
        $this->ownerId = $ownerId;
        $this->visibility = $visibility;
        $this->status = MixStatus::PENDING;
        $this->createdAt = DateTime::now();

        $this->recordEvent(new MixCreated($this->id));
    }

    public static function create(
        TrackMetadata $metadata,
        UserId $ownerId,
        ?Visibility $visibility = null,
    ): self {
        return new self(
            MixId::generate(), // 👈 UUID v7 генерируется здесь
            $metadata,
            $ownerId,
            $visibility ?? Visibility::public(),
        );
    }

    /**
     * Восстановление существующего микса из хранилища
     */
    public static function restore(
        MixId $id,
        TrackMetadata $metadata,
        UserId $ownerId,
        Visibility $visibility,
        MixStatus $status,
        ?FileId $originalFileId,
        ?FileId $streamFileId,
        ?FileId $peaksFileId,
        ?FileSize $originalFileSize,
        ?FileSize $streamFileSize,
        ?FileSize $peaksFileSize,
        ?Duration $duration,
        DateTime $createdAt,
        ?DateTime $processedAt,
    ): self {
        $mix = new self($id, $metadata, $ownerId, $visibility);
        $mix->status = $status;
        $mix->originalFileId = $originalFileId;
        $mix->streamFileId = $streamFileId;
        $mix->peaksFileId = $peaksFileId;
        $mix->originalFileSize = $originalFileSize;
        $mix->streamFileSize = $streamFileSize;
        $mix->peaksFileSize = $peaksFileSize;
        $mix->duration = $duration;
        $mix->createdAt = $createdAt;
        $mix->processedAt = $processedAt;

        return $mix;
    }

    // ========================================
    // 2. БИЗНЕС-МЕТОДЫ (ЖИЗНЕННЫЙ ЦИКЛ)
    // ========================================

    public function attachOriginalFile(FileId $fileId, FileSize $fileSize): void
    {
        if ($this->originalFileId !== null) {
            throw new \RuntimeException('Original file already attached');
        }

        if (!$this->status->canTransitionTo(MixStatus::PROCESSING)) {
            throw new InvalidTransitionException(
                sprintf('Cannot attach file to mix in status "%s"', $this->status->value)
            );
        }

        $this->originalFileId = $fileId;
        $this->originalFileSize = $fileSize;
    }

    public function startProcessing(): void
    {
        if ($this->originalFileId === null) {
            throw new NoOriginalFileException('Cannot process mix without original file');
        }

        if (!$this->status->canTransitionTo(MixStatus::PROCESSING)) {
            throw new InvalidTransitionException(
                sprintf('Cannot start processing in status "%s"', $this->status->value)
            );
        }

        $this->status = MixStatus::PROCESSING;
        $this->recordEvent(new MixProcessingStarted($this->id));
    }

    public function markConversionCompleted(FileId $streamFileId, FileSize $fileSize): void
    {
        if ($this->status !== MixStatus::PROCESSING) {
            throw new InvalidTransitionException(
                sprintf('Cannot mark conversion completed in status "%s"', $this->status->value)
            );
        }

        $this->streamFileId = $streamFileId;
        $this->streamFileSize = $fileSize;
        $this->recordEvent(new ConversionCompleted($this->id));
    }

    public function markAnalysisCompleted(FileId $peaksFileId, FileSize $fileSize, Duration $duration): void
    {
        if ($this->status !== MixStatus::PROCESSING) {
            throw new InvalidTransitionException(
                sprintf('Cannot mark analysis completed in status "%s"', $this->status->value)
            );
        }

        $this->peaksFileId = $peaksFileId;
        $this->peaksFileSize = $fileSize;
        $this->duration = $duration;
        $this->recordEvent(new AnalysisCompleted($this->id));
    }

    public function markUploadCompleted(): void
    {
        if ($this->status !== MixStatus::PROCESSING) {
            throw new InvalidTransitionException(
                sprintf('Cannot mark upload completed in status "%s"', $this->status->value)
            );
        }

        if ($this->streamFileId === null || $this->peaksFileId === null) {
            throw new \RuntimeException('Cannot complete upload: stream or peaks file missing');
        }

        $this->status = MixStatus::READY;
        $this->processedAt = DateTime::now();
        $this->recordEvent(new MixCompleted($this->id));
    }

    public function markAsFailed(string $reason): void
    {
        if ($this->status === MixStatus::READY) {
            throw new InvalidTransitionException('Cannot fail a completed mix');
        }

        $this->status = MixStatus::FAILED;
        $this->recordEvent(new MixProcessingFailed($this->id, $reason));
    }

    // ========================================
    // 3. БИЗНЕС-МЕТОДЫ (ИЗМЕНЕНИЕ СОСТОЯНИЯ)
    // ========================================

    public function changeVisibility(Visibility $newVisibility): void
    {
        if ($this->visibility === $newVisibility) {
            return;
        }

        $this->visibility = $newVisibility;

        if ($newVisibility->isPublic()) {
            $this->recordEvent(new MixMadePublic($this->id));
        } else {
            $this->recordEvent(new MixMadePrivate($this->id));
        }
    }

    public function makePublic(): void
    {
        $this->changeVisibility(Visibility::public());
    }

    public function makePrivate(): void
    {
        $this->changeVisibility(Visibility::private());
    }

    // ========================================
    // 4. ПРОВЕРКИ (ГЕТТЕРЫ-ФЛАГИ)
    // ========================================

    public function isPending(): bool
    {
        return $this->status === MixStatus::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === MixStatus::PROCESSING;
    }

    public function isReady(): bool
    {
        return $this->status === MixStatus::READY;
    }

    public function isFailed(): bool
    {
        return $this->status === MixStatus::FAILED;
    }

    public function isPublic(): bool
    {
        return $this->visibility->isPublic();
    }

    public function isPrivate(): bool
    {
        return $this->visibility->isPrivate();
    }

    public function hasOriginalFile(): bool
    {
        return $this->originalFileId !== null;
    }

    public function hasStreamFile(): bool
    {
        return $this->streamFileId !== null;
    }

    public function hasPeaksFile(): bool
    {
        return $this->peaksFileId !== null;
    }

    public function isComplete(): bool
    {
        return $this->isReady()
            && $this->originalFileId !== null
            && $this->streamFileId !== null
            && $this->peaksFileId !== null
            && $this->duration !== null;
    }

    // ========================================
    // 5. ПРОВЕРКИ ПРАВ ДОСТУПА
    // ========================================

    public function canBeViewedBy(UserId $userId): bool
    {
        // Владелец всегда может видеть
        if ($this->ownerId->equals($userId)) {
            return true;
        }

        // Публичный микс могут видеть все
        if ($this->isPublic()) {
            return true;
        }

        // Приватный микс могут видеть только владелец
        return false;
    }

    public function canBeDownloadedBy(UserId $userId): bool
    {
        // Скачивать может только владелец
        return $this->ownerId->equals($userId);
    }

    public function canBeEditedBy(UserId $userId): bool
    {
        // Редактировать может только владелец
        return $this->ownerId->equals($userId);
    }

    public function canBeDeletedBy(UserId $userId): bool
    {
        // Удалять может только владелец
        return $this->ownerId->equals($userId);
    }

    // ========================================
    // 6. ГЕТТЕРЫ (ТОЛЬКО ДЛЯ ЧТЕНИЯ)
    // ========================================

    public function getId(): MixId
    {
        return $this->id;
    }

    public function getMetadata(): TrackMetadata
    {
        return $this->metadata;
    }

    public function getOwnerId(): UserId
    {
        return $this->ownerId;
    }

    public function getStatus(): MixStatus
    {
        return $this->status;
    }

    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    public function getOriginalFileId(): ?FileId
    {
        return $this->originalFileId;
    }

    public function getStreamFileId(): ?FileId
    {
        return $this->streamFileId;
    }

    public function getPeaksFileId(): ?FileId
    {
        return $this->peaksFileId;
    }

    public function getOriginalFileSize(): ?FileSize
    {
        return $this->originalFileSize;
    }

    public function getStreamFileSize(): ?FileSize
    {
        return $this->streamFileSize;
    }

    public function getPeaksFileSize(): ?FileSize
    {
        return $this->peaksFileSize;
    }

    public function getTotalSize(): TotalSize
    {
        return new TotalSize(
            $this->originalFileSize ?? new FileSize(0),
            $this->streamFileSize ?? new FileSize(0),
            $this->peaksFileSize ?? new FileSize(0)
        );
    }

    public function getDuration(): ?Duration
    {
        return $this->duration;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getProcessedAt(): ?DateTime
    {
        return $this->processedAt;
    }

    // ========================================
    // 7. СОБЫТИЯ
    // ========================================

    private function recordEvent(object $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return array<object>
     */
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    // ========================================
    // 8. МАГИЧЕСКИЕ МЕТОДЫ (ДЛЯ ДЕБАГА)
    // ========================================

    public function __toString(): string
    {
        return sprintf(
            'Mix #%s: "%s" by "%s" (%s)',
            $this->id->toString(),
            $this->metadata->getTitle()->toString(),
            $this->metadata->getArtist()->toString(),
            $this->status->value
        );
    }
}
