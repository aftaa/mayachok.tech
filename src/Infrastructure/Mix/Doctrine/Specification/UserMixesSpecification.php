<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification;

use App\Domain\Mix\Specification\UserMixesSpecification;

final class UserMixesSpecificationAdapter implements DoctrineSpecificationInterface
{
    public function __construct(
        private readonly UserMixesSpecification $specification,
    ) {}

    public function toDQL(string $alias): string
    {
        return sprintf('%s.user = :userId', $alias);
    }

    public function getParameters(): array
    {
        return [
            'userId' => $this->specification->getUserId()->toInt(),
        ];
    }

    public function getJoins(): array
    {
        return [];
    }

    public static function key(): string
    {
        return UserMixesSpecification::class;
    }
}
