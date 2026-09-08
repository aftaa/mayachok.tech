<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification\Adapter;

use App\Domain\Mix\Specification\FailedMixesSpecification;
use App\Infrastructure\Mix\Doctrine\Specification\Contract\DoctrineSpecificationInterface;

final class FailedMixesSpecificationAdapter implements DoctrineSpecificationInterface
{
    public function __construct(
        private readonly FailedMixesSpecification $specification,
    ) {}

    public function toDQL(string $alias): string
    {
        return sprintf('%s.status = :failedStatus', $alias);
    }

    public function getParameters(): array
    {
        return [
            'failedStatus' => 'failed',
        ];
    }

    public function getJoins(): array
    {
        return [];
    }

    public static function key(): string
    {
        return FailedMixesSpecification::class;
    }
}
