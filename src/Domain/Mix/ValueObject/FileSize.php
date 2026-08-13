<?php

declare(strict_types=1);

namespace App\Domain\Mix\ValueObject;

final class FileSize
{
    private int $bytes;

    public function __construct(int $bytes)
    {
        if ($bytes < 0) {
            throw new \InvalidArgumentException('File size cannot be negative');
        }
        $this->bytes = $bytes;
    }

    public function toBytes(): int
    {
        return $this->bytes;
    }

    public function toKilobytes(): float
    {
        return $this->bytes / 1024;
    }

    public function toMegabytes(): float
    {
        return $this->bytes / 1024 / 1024;
    }

    public function toGigabytes(): float
    {
        return $this->bytes / 1024 / 1024 / 1024;
    }

    public function format(string $unit = 'MB'): string
    {
        return match($unit) {
            'KB' => number_format($this->toKilobytes(), 2) . ' KB',
            'MB' => number_format($this->toMegabytes(), 2) . ' MB',
            'GB' => number_format($this->toGigabytes(), 2) . ' GB',
            default => number_format($this->toBytes(), 0) . ' B',
        };
    }

    public function isZero(): bool
    {
        return $this->bytes === 0;
    }

    public function add(self $other): self
    {
        return new self($this->bytes + $other->bytes);
    }

    public function equals(self $other): bool
    {
        return $this->bytes === $other->bytes;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->bytes > $other->bytes;
    }

    public function isLessThan(self $other): bool
    {
        return $this->bytes < $other->bytes;
    }

    public function isWithinLimit(self $limit): bool
    {
        return $this->bytes <= $limit->bytes;
    }
}
