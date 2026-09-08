<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification\Adapter;

use App\Domain\Mix\Specification\PublicMixesSpecification;
use App\Infrastructure\Mix\Doctrine\Specification\Contract\DoctrineSpecificationInterface;

final class PublicMixesSpecificationAdapter implements DoctrineSpecificationInterface
{
    public function __construct(
        private readonly PublicMixesSpecification $specification,
    ) {}

    public function toDQL(string $alias): string
    {
        return sprintf('%s.isPrivate = false', $alias);
    }

    public function getParameters(): array
    {
        return [];
    }

    public function getJoins(): array
    {
        return [];
    }

    public static function key(): string
    {
        return PublicMixesSpecification::class;
    }
}
