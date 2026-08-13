<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification;

use App\Domain\Mix\Specification\CompletedMixesSpecification;

final class CompletedMixesSpecificationAdapter implements DoctrineSpecificationInterface
{
    public function __construct(
        private readonly CompletedMixesSpecification $specification,
    ) {}

    public function toDQL(string $alias): string
    {
        return sprintf(
            '%s.isProcessed = true AND %s.s3StreamKey IS NOT NULL AND %s.peaksKey IS NOT NULL',
            $alias,
            $alias,
            $alias
        );
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
        return CompletedMixesSpecification::class;
    }
}
