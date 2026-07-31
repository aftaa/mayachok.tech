<?php

namespace App\Specification;

interface SpecificationInterface
{
    public function toDQL(string $alias): ?string;
    public function getParameters(): array;
    public function getJoins(): array;
}
