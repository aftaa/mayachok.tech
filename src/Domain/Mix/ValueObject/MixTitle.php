<?php

declare(strict_types=1);

namespace App\Domain\Mix\ValueObject;

final class MixTitle
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException('Mix title cannot be empty');
        }
        if (strlen($value) > 255) {
            throw new \InvalidArgumentException('Mix title too long (max 255 characters)');
        }
        if (preg_match('/[<>"\'\/;]/', $value)) {
            throw new \InvalidArgumentException('Mix title contains invalid characters');
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
