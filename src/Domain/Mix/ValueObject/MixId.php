<?php

declare(strict_types=1);

namespace App\Domain\Mix\ValueObject;

use Symfony\Component\Uid\Uuid;

final class MixId
{
    private Uuid $value;

    private function __construct(Uuid $value)
    {
        $this->value = $value;
    }

    public static function generate(): self
    {
        return new self(Uuid::v7());
    }

    public static function fromString(string $value): self
    {
        return new self(Uuid::fromString($value));
    }

    public static function fromUuid(Uuid $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value->toRfc4122();
    }

    public function toBinary(): string
    {
        return $this->value->toBinary();
    }

    public function getValue(): Uuid
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
