<?php

namespace App\Domain\Mix\Specification;

use App\Domain\Mix\Entity\Mix;

interface SpecificationInterface
{
    public function isSatisfiedBy(Mix $mix): bool;
}
