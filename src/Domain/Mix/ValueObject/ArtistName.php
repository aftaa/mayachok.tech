<?php

declare(strict_types=1);

namespace App\Domain\Mix\ValueObject;

final class ArtistName
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Artist name cannot be empty');
        }
        if (strlen($value) > 255) {
            throw new \InvalidArgumentException('Artist name too long (max 255 characters)');
        }
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
