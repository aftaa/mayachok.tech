<?php

declare(strict_types=1);

namespace App\Domain\Mix\Specification;

use App\Domain\Mix\Entity\Mix;
use App\Domain\User\ValueObject\UserId;

final class FavoriteMixesSpecification implements SpecificationInterface
{
    public function __construct(
        private readonly UserId $userId,
    ) {}

    public function isSatisfiedBy(Mix $mix): bool
    {
        // Проверяем, есть ли пользователь в списке избранных
        // Внимание: это требует загрузки коллекции! Для репозитория будет отдельный адаптер
        return $mix->isFavoritedBy($this->userId);
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }
}
