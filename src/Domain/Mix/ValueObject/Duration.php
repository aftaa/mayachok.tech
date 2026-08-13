<?php

declare(strict_types=1);

namespace App\Domain\Mix\ValueObject;

final class Duration
{
    private int $seconds;

    public function __construct(int $seconds)
    {
        if ($seconds < 0) {
            throw new \InvalidArgumentException('Duration cannot be negative');
        }
        $this->seconds = $seconds;
    }

    public function toSeconds(): int
    {
        return $this->seconds;
    }

    public function toMinutes(): float
    {
        return $this->seconds / 60;
    }

    public function format(): string
    {
        $hours = (int) floor($this->seconds / 3600);
        $minutes = (int) floor(($this->seconds % 3600) / 60);
        $seconds = $this->seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function equals(self $other): bool
    {
        return $this->seconds === $other->seconds;
    }
}
