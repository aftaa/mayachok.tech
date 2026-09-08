<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification\Adapter;

use App\Domain\Mix\Specification\PendingMixesSpecification;
use App\Infrastructure\Mix\Doctrine\Specification\Contract\DoctrineSpecificationInterface;

final class PendingMixesSpecificationAdapter implements DoctrineSpecificationInterface
{
    public function __construct(
        private readonly PendingMixesSpecification $specification,
    ) {}

    public function toDQL(string $alias): string
    {
        return sprintf('%s.status = :pendingStatus', $alias);
    }

    public function getParameters(): array
    {
        return [
            'pendingStatus' => 'pending',
        ];
    }

    public function getJoins(): array
    {
        return [];
    }

    public static function key(): string
    {
        return PendingMixesSpecification::class;
    }
}
