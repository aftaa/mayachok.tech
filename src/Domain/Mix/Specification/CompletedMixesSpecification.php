<?php

declare(strict_types=1);

namespace App\Domain\Mix\Specification;

use App\Domain\Mix\Entity\Mix;

final class CompletedMixesSpecification implements SpecificationInterface
{
    public function isSatisfiedBy(Mix $mix): bool
    {
        return $mix->isReady() && $mix->isComplete();
    }
}
