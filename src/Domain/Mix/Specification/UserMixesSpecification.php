<?php

declare(strict_types=1);

namespace App\Domain\Mix\Specification;

use App\Domain\Mix\Entity\Mix;
use App\Domain\User\ValueObject\UserId;

final class UserMixesSpecification implements SpecificationInterface
{
    public function __construct(
        private readonly UserId $userId,
    ) {}

    public function isSatisfiedBy(Mix $mix): bool
    {
        return $mix->getOwnerId()->equals($this->userId);
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }
}
