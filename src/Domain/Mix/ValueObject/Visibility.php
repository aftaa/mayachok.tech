<?php

declare(strict_types=1);

namespace App\Domain\Mix\ValueObject;

enum Visibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';

    public function isPublic(): bool
    {
        return $this === self::PUBLIC;
    }

    public function isPrivate(): bool
    {
        return $this === self::PRIVATE;
    }

    public static function public(): self
    {
        return self::PUBLIC;
    }

    public static function private(): self
    {
        return self::PRIVATE;
    }
}
