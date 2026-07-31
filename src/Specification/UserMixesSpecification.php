<?php

namespace App\Specification;

readonly class UserMixesSpecification implements SpecificationInterface
{
    public function __construct(
        private int $userId
    ) {}

    public function toDQL(string $alias): string
    {
        return "{$alias}.user = :userId";
    }

    public function getParameters(): array
    {
        return ['userId' => $this->userId];
    }

    public function getJoins(): array
    {
        return [];
    }
}
