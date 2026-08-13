<?php

declare(strict_types=1);

namespace App\Domain\Mix\Repository;

use App\Domain\Mix\Entity\Mix;
use App\Domain\Mix\Specification\SpecificationInterface;
use App\Domain\Mix\ValueObject\MixId;

interface MixRepositoryInterface
{
    /**
     * Сохранить микс
     */
    public function save(Mix $mix): void;

    /**
     * Удалить микс
     */
    public function delete(Mix $mix): void;

    /**
     * Найти микс по ID
     */
    public function findById(MixId $id): ?Mix;

    /**
     * Найти микс по UUID (для публичных ссылок)
     */
    public function findByUuid(string $uuid): ?Mix;

    /**
     * Найти все миксы, соответствующие спецификации
     *
     * @return Mix[]
     */
    public function findMatches(SpecificationInterface $specification): array;

    /**
     * Подсчитать количество миксов, соответствующих спецификации
     */
    public function countMatches(SpecificationInterface $specification): int;

    /**
     * Проверить, существует ли микс с таким ID
     */
    public function exists(MixId $id): bool;
}
