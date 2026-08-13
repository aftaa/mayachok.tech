<?php

declare(strict_types=1);

namespace App\Domain\Mix\ValueObject;

final class FileId
{
    private string $value;

    private function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('FileId cannot be empty');
        }
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function fromPath(string $path): self
    {
        return new self(basename($path));
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
