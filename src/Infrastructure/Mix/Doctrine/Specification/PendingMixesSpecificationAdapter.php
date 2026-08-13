<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification;

use App\Domain\Mix\Specification\PendingMixesSpecification;

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
