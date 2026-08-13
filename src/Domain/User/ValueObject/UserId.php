<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

final class UserId
{
    private string $value;

    private function __construct(string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('Invalid UserId format. Expected valid UUID.');
        }
        $this->value = $value;
    }

    /**
     * Создать новый UserId (генерация UUID v4)
     */
    public static function generate(): self
    {
        return new self(Uuid::v4()->toString());
    }

    /**
     * Создать UserId из строки
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * Создать UserId из целого числа (для обратной совместимости с Legacy)
     */
    public static function fromInt(int $id): self
    {
        // Конвертируем int в UUID-подобный формат или просто в строку
        // Вариант 1: Используем как есть (если Legacy использовал int)
        // return new self((string) $id);

        // Вариант 2: Генерируем детерминированный UUID из int
        return new self(
            sprintf(
                '00000000-0000-0000-0000-%012d',
                $id
            )
        );
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toInt(): int
    {
        // Если в БД хранится как int, а в Domain как UUID
        // Этот метод нужен для обратной совместимости
        $hex = str_replace('-', '', $this->value);
        return (int) hexdec(substr($hex, -12));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isEqualTo(?self $other): bool
    {
        return $other !== null && $this->equals($other);
    }

    public function isEmpty(): bool
    {
        return empty($this->value);
    }
}
