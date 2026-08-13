<?php

declare(strict_types=1);

namespace App\Domain\Mix\Specification;

use App\Domain\Mix\Entity\Mix;

final class AndSpecification implements SpecificationInterface
{
    /**
     * @param SpecificationInterface[] $specifications
     */
    public function __construct(
        private readonly array $specifications,
    ) {
        if (count($this->specifications) < 2) {
            throw new \InvalidArgumentException('AndSpecification requires at least 2 specifications');
        }
    }

    public function isSatisfiedBy(Mix $mix): bool
    {
        foreach ($this->specifications as $spec) {
            if (!$spec->isSatisfiedBy($mix)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return SpecificationInterface[]
     */
    public function getSpecifications(): array
    {
        return $this->specifications;
    }
}
