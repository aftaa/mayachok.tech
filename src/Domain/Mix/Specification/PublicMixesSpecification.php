<?php

declare(strict_types=1);

namespace App\Domain\Mix\Specification;

use App\Domain\Mix\Entity\Mix;

final class PublicMixesSpecification implements SpecificationInterface
{
    public function isSatisfiedBy(Mix $mix): bool
    {
        return $mix->isPublic();
    }
}
