<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification\Adapter;

use App\Domain\Mix\Specification\FavoriteMixesSpecification;
use App\Infrastructure\Mix\Doctrine\Specification\Contract\DoctrineSpecificationInterface;

final class FavoriteMixesSpecificationAdapter implements DoctrineSpecificationInterface
{
    public function __construct(
        private readonly FavoriteMixesSpecification $specification,
    ) {}

    public function toDQL(string $alias): string
    {
        return 'f.user = :favoriteUserId';
    }

    public function getParameters(): array
    {
        return [
            'favoriteUserId' => $this->specification->getUserId()->toInt(),
        ];
    }

    public function getJoins(): array
    {
        return [
            sprintf('INNER JOIN %s.favoritedBy f', $alias),
        ];
    }

    public static function key(): string
    {
        return FavoriteMixesSpecification::class;
    }
}
