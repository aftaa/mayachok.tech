<?php

declare(strict_types=1);

namespace App\Domain\Mix\Specification;

use App\Domain\Mix\Entity\Mix;

final class OrSpecification implements SpecificationInterface
{
    /**
     * @param SpecificationInterface[] $specifications
     */
    public function __construct(
        private readonly array $specifications,
    ) {
        if (count($this->specifications) < 2) {
            throw new \InvalidArgumentException('OrSpecification requires at least 2 specifications');
        }
    }

    public function isSatisfiedBy(Mix $mix): bool
    {
        foreach ($this->specifications as $spec) {
            if ($spec->isSatisfiedBy($mix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return SpecificationInterface[]
     */
    public function getSpecifications(): array
    {
        return $this->specifications;
    }
}
