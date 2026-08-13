<?php

declare(strict_types=1);

namespace App\Domain\Mix\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

final class MixId
{
    private string $value;

    private function __construct(string $value)
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException('Invalid MixId format');
        }
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toString());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
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
