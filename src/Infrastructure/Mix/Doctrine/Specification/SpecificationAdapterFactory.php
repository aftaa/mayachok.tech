<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification;

use App\Domain\Mix\Specification\AndSpecification;
use App\Domain\Mix\Specification\OrSpecification;
use App\Domain\Mix\Specification\NotSpecification;
use App\Domain\Mix\Specification\SpecificationInterface;

final class SpecificationAdapterFactory
{
    /**
     * @var array<string, DoctrineSpecificationInterface>
     */
    private array $adapters = [];

    /**
     * @param iterable<DoctrineSpecificationInterface> $adapters
     */
    public function __construct(
        iterable $adapters,
    ) {
        foreach ($adapters as $adapter) {
            $key = $adapter::key();
            if ($key !== null) {
                $this->adapters[$key] = $adapter;
            }
        }
    }

    public function create(SpecificationInterface $specification): DoctrineSpecificationInterface
    {
        $key = $specification::class;

        // Если адаптер для простой спецификации
        if (isset($this->adapters[$key])) {
            return $this->adapters[$key];
        }

        // Композитные спецификации обрабатываем отдельно
        return match ($key) {
            AndSpecification::class => new AndSpecificationAdapter($specification, $this),
            OrSpecification::class => new OrSpecificationAdapter($specification, $this),
            NotSpecification::class => new NotSpecificationAdapter($specification, $this),
            default => throw new \RuntimeException(
                sprintf('No adapter found for specification: %s', $key)
            ),
        };
    }
}
