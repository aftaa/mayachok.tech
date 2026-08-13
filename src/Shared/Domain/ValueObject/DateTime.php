<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final class DateTime
{
    private \DateTimeImmutable $value;

    private function __construct(\DateTimeImmutable $value)
    {
        $this->value = $value;
    }

    // ========================================
    // 1. ФАБРИКИ (СОЗДАНИЕ)
    // ========================================

    /**
     * Текущий момент времени (сейчас)
     */
    public static function now(): self
    {
        return new self(new \DateTimeImmutable());
    }

    /**
     * Создать из строки (любой формат, который понимает DateTimeImmutable)
     */
    public static function fromString(string $dateTime): self
    {
        try {
            $value = new \DateTimeImmutable($dateTime);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                sprintf('Invalid date/time format: "%s". Expected valid date string.', $dateTime),
                previous: $e
            );
        }

        return new self($value);
    }

    /**
     * Создать из DateTimeImmutable
     */
    public static function fromDateTimeImmutable(\DateTimeImmutable $dateTime): self
    {
        return new self($dateTime);
    }

    /**
     * Создать из DateTime (модифицируемый → преобразуем в неизменяемый)
     */
    public static function fromDateTime(\DateTime $dateTime): self
    {
        return new self(\DateTimeImmutable::createFromMutable($dateTime));
    }

    /**
     * Создать из timestamp (Unix)
     */
    public static function fromTimestamp(int $timestamp): self
    {
        return new self(
            (new \DateTimeImmutable())->setTimestamp($timestamp)
        );
    }

    /**
     * Создать из формата Y-m-d H:i:s
     */
    public static function fromDatabase(string $datetime): self
    {
        return self::fromString($datetime);
    }

    // ========================================
    // 2. МОДИФИКАЦИИ (ВОЗВРАЩАЮТ НОВЫЙ ОБЪЕКТ)
    // ========================================

    /**
     * Добавить интервал
     */
    public function add(string $interval): self
    {
        try {
            $newValue = $this->value->add(new \DateInterval($interval));
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                sprintf('Invalid date interval: "%s"', $interval),
                previous: $e
            );
        }

        return new self($newValue);
    }

    /**
     * Вычесть интервал
     */
    public function sub(string $interval): self
    {
        try {
            $newValue = $this->value->sub(new \DateInterval($interval));
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                sprintf('Invalid date interval: "%s"', $interval),
                previous: $e
            );
        }

        return new self($newValue);
    }

    /**
     * Добавить дни
     */
    public function addDays(int $days): self
    {
        return $this->add("P{$days}D");
    }

    /**
     * Добавить часы
     */
    public function addHours(int $hours): self
    {
        return $this->add("PT{$hours}H");
    }

    /**
     * Добавить минуты
     */
    public function addMinutes(int $minutes): self
    {
        return $this->add("PT{$minutes}M");
    }

    /**
     * Добавить секунды
     */
    public function addSeconds(int $seconds): self
    {
        return $this->add("PT{$seconds}S");
    }

    // ========================================
    // 3. СРАВНЕНИЯ
    // ========================================

    public function equals(self $other): bool
    {
        return $this->value == $other->value;
    }

    public function isBefore(self $other): bool
    {
        return $this->value < $other->value;
    }

    public function isAfter(self $other): bool
    {
        return $this->value > $other->value;
    }

    public function isBetween(self $start, self $end): bool
    {
        return $this->isAfter($start) && $this->isBefore($end);
    }

    public function isSameDay(self $other): bool
    {
        return $this->format('Y-m-d') === $other->format('Y-m-d');
    }

    public function isToday(): bool
    {
        return $this->isSameDay(self::now());
    }

    public function isFuture(): bool
    {
        return $this->isAfter(self::now());
    }

    public function isPast(): bool
    {
        return $this->isBefore(self::now());
    }

    // ========================================
    // 4. РАЗНИЦА МЕЖДУ ДАТАМИ
    // ========================================

    public function diff(self $other): \DateInterval
    {
        return $this->value->diff($other->value);
    }

    public function diffInSeconds(self $other): int
    {
        return abs($this->value->getTimestamp() - $other->value->getTimestamp());
    }

    public function diffInMinutes(self $other): int
    {
        return (int) ($this->diffInSeconds($other) / 60);
    }

    public function diffInHours(self $other): int
    {
        return (int) ($this->diffInMinutes($other) / 60);
    }

    public function diffInDays(self $other): int
    {
        return (int) ($this->diffInHours($other) / 24);
    }

    public function formatHumanDiff(self $other): string
    {
        $diff = $this->diffInSeconds($other);

        if ($diff < 60) {
            return $diff . ' seconds';
        }
        if ($diff < 3600) {
            return round($diff / 60) . ' minutes';
        }
        if ($diff < 86400) {
            return round($diff / 3600) . ' hours';
        }
        return round($diff / 86400) . ' days';
    }

    // ========================================
    // 5. ФОРМАТИРОВАНИЕ
    // ========================================

    public function format(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->value->format($format);
    }

    public function toDateString(): string
    {
        return $this->format('Y-m-d');
    }

    public function toTimeString(): string
    {
        return $this->format('H:i:s');
    }

    public function toDateTimeString(): string
    {
        return $this->format('Y-m-d H:i:s');
    }

    public function toISO8601(): string
    {
        return $this->value->format(\DateTimeInterface::ATOM);
    }

    public function toRSS(): string
    {
        return $this->value->format(\DateTimeInterface::RSS);
    }

    public function toCookie(): string
    {
        return $this->value->format(\DateTimeInterface::COOKIE);
    }

    // ========================================
    // 6. ГЕТТЕРЫ
    // ========================================

    public function getTimestamp(): int
    {
        return $this->value->getTimestamp();
    }

    public function getYear(): int
    {
        return (int) $this->format('Y');
    }

    public function getMonth(): int
    {
        return (int) $this->format('m');
    }

    public function getDay(): int
    {
        return (int) $this->format('d');
    }

    public function getHour(): int
    {
        return (int) $this->format('H');
    }

    public function getMinute(): int
    {
        return (int) $this->format('i');
    }

    public function getSecond(): int
    {
        return (int) $this->format('s');
    }

    public function getDayOfWeek(): int
    {
        return (int) $this->format('w');
    }

    public function getDayOfYear(): int
    {
        return (int) $this->format('z');
    }

    public function getWeekNumber(): int
    {
        return (int) $this->format('W');
    }

    public function getTimezone(): \DateTimeZone
    {
        return $this->value->getTimezone();
    }

    /**
     * Получить внутренний объект DateTimeImmutable
     */
    public function toDateTimeImmutable(): \DateTimeImmutable
    {
        return $this->value;
    }

    // ========================================
    // 7. МАГИЧЕСКИЕ МЕТОДЫ
    // ========================================

    public function __toString(): string
    {
        return $this->toDateTimeString();
    }

    public function jsonSerialize(): string
    {
        return $this->toISO8601();
    }

    // ========================================
    // 8. БОНУС: ПРЕДОПРЕДЕЛЕННЫЕ ДАТЫ
    // ========================================

    public static function min(): self
    {
        return self::fromString('1970-01-01 00:00:00');
    }

    public static function max(): self
    {
        return self::fromString('9999-12-31 23:59:59');
    }

    public static function empty(): self
    {
        return self::fromString('0000-00-00 00:00:00');
    }
}
