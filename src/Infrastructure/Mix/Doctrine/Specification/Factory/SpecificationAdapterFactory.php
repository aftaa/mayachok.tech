<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification\Factory;

use App\Domain\Mix\Specification\AndSpecification;
use App\Domain\Mix\Specification\CompletedMixesSpecification;
use App\Domain\Mix\Specification\FailedMixesSpecification;
use App\Domain\Mix\Specification\FavoriteMixesSpecification;
use App\Domain\Mix\Specification\NotSpecification;
use App\Domain\Mix\Specification\OrSpecification;
use App\Domain\Mix\Specification\PendingMixesSpecification;
use App\Domain\Mix\Specification\ProcessedMixesSpecification;
use App\Domain\Mix\Specification\PublicMixesSpecification;
use App\Domain\Mix\Specification\SpecificationInterface;
use App\Domain\Mix\Specification\UserMixesSpecification;
use App\Infrastructure\Mix\Doctrine\Specification\Adapter\AndSpecificationAdapter;
use App\Infrastructure\Mix\Doctrine\Specification\Adapter\CompletedMixesSpecificationAdapter;
use App\Infrastructure\Mix\Doctrine\Specification\Adapter\FailedMixesSpecificationAdapter;
use App\Infrastructure\Mix\Doctrine\Specification\Adapter\FavoriteMixesSpecificationAdapter;
use App\Infrastructure\Mix\Doctrine\Specification\Adapter\NotSpecificationAdapter;
use App\Infrastructure\Mix\Doctrine\Specification\Adapter\OrSpecificationAdapter;
use App\Infrastructure\Mix\Doctrine\Specification\Adapter\PendingMixesSpecificationAdapter;
use App\Infrastructure\Mix\Doctrine\Specification\Adapter\ProcessedMixesSpecificationAdapter;
use App\Infrastructure\Mix\Doctrine\Specification\Adapter\PublicMixesSpecificationAdapter;
use App\Infrastructure\Mix\Doctrine\Specification\Adapter\UserMixesSpecificationAdapter;
use App\Infrastructure\Mix\Doctrine\Specification\Contract\DoctrineSpecificationInterface;

final class SpecificationAdapterFactory
{
    public function create(SpecificationInterface $specification): DoctrineSpecificationInterface
    {
        return match ($specification::class) {
            // ========================================
            // ПРОСТЫЕ СПЕЦИФИКАЦИИ
            // ========================================
            PublicMixesSpecification::class => new PublicMixesSpecificationAdapter($specification),
            UserMixesSpecification::class => new UserMixesSpecificationAdapter($specification),
            FavoriteMixesSpecification::class => new FavoriteMixesSpecificationAdapter($specification),
            ProcessedMixesSpecification::class => new ProcessedMixesSpecificationAdapter($specification),
            CompletedMixesSpecification::class => new CompletedMixesSpecificationAdapter($specification),
            FailedMixesSpecification::class => new FailedMixesSpecificationAdapter($specification),
            PendingMixesSpecification::class => new PendingMixesSpecificationAdapter($specification),

            // ========================================
            // КОМПОЗИТНЫЕ СПЕЦИФИКАЦИИ (передаем фабрику)
            // ========================================
            AndSpecification::class => new AndSpecificationAdapter($specification, $this),
            OrSpecification::class => new OrSpecificationAdapter($specification, $this),
            NotSpecification::class => new NotSpecificationAdapter($specification, $this),

            default => throw new \RuntimeException(
                sprintf('No adapter found for specification: %s', $specification::class)
            ),
        };
    }
}
