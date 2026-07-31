<?php

namespace App\Specification;

class PublicMixesSpecification implements SpecificationInterface
{
    public function toDQL(string $alias): string
    {
        return "{$alias}.isPrivate = false";
    }

    public function getParameters(): array
    {
        return [];
    }

    public function getJoins(): array
    {
        return [];
    }
}
