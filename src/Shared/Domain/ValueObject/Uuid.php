<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Symfony\Component\Uid\Uuid as SymfonyUuid;

final class Uuid
{
    private string $value;

    private function __construct(string $value)
    {
        if (!self::isValid($value)) {
            throw new \InvalidArgumentException('Invalid UUID format');
        }
        $this->value = $value;
    }

    public static function v4(): self
    {
        return new self(SymfonyUuid::v4()->toRfc4122());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function isValid(string $value): bool
    {
        return SymfonyUuid::isValid($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toBinary(): string
    {
        return (string) hex2bin(str_replace('-', '', $this->value));
    }

    public function toRfc4122(): string
    {
        return $this->value;
    }
}
