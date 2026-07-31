<?php

namespace App\Specification;

readonly class FavoriteMixesSpecification implements SpecificationInterface
{
    public function __construct(
        private int $userId
    ) {}

    public function toDQL(string $alias): string
    {
        return "f.user = :favoriteUserId";
    }

    public function getParameters(): array
    {
        return ['favoriteUserId' => $this->userId];
    }

    public function getJoins(): array
    {
        return [
            "INNER JOIN {$alias}.favoritedBy f"
        ];
    }
}
