<?php

declare(strict_types=1);

namespace App\Domain\Mix\Specification;

use App\Domain\Mix\Entity\Mix;

final class NotSpecification implements SpecificationInterface
{
    public function __construct(
        private readonly SpecificationInterface $specification,
    ) {}

    public function isSatisfiedBy(Mix $mix): bool
    {
        return !$this->specification->isSatisfiedBy($mix);
    }

    public function getSpecification(): SpecificationInterface
    {
        return $this->specification;
    }
}
