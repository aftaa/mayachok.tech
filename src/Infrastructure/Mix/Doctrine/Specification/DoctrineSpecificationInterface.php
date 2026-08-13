<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification;

interface DoctrineSpecificationInterface
{
    /**
     * Преобразует спецификацию в DQL-условие
     */
    public function toDQL(string $alias): ?string;

    /**
     * Возвращает параметры для DQL-запроса
     */
    public function getParameters(): array;

    /**
     * Возвращает JOIN-ы для DQL-запроса
     */
    public function getJoins(): array;

    /**
     * Уникальный ключ адаптера (для автоматической регистрации)
     */
    public static function key(): ?string;
}
