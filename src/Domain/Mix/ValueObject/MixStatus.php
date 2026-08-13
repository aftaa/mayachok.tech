<?php

declare(strict_types=1);

namespace App\Domain\Mix\ValueObject;

enum MixStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case READY = 'ready';
    case FAILED = 'failed';

    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [self::PROCESSING, self::FAILED], true),
            self::PROCESSING => in_array($newStatus, [self::READY, self::FAILED], true),
            self::READY => false, // Нельзя изменить готовый микс
            self::FAILED => in_array($newStatus, [self::PENDING]), // Можно перезапустить
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Ожидает обработки',
            self::PROCESSING => 'Обрабатывается',
            self::READY => 'Готов к прослушиванию',
            self::FAILED => 'Ошибка обработки',
        };
    }
}
