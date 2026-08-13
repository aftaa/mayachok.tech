<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification;

use App\Domain\Mix\Specification\AndSpecification;

final class AndSpecificationAdapter implements DoctrineSpecificationInterface
{
    /**
     * @var DoctrineSpecificationInterface[]
     */
    private array $adapters = [];

    public function __construct(
        private readonly AndSpecification $specification,
        SpecificationAdapterFactory $factory,
    ) {
        foreach ($specification->getSpecifications() as $spec) {
            $this->adapters[] = $factory->create($spec);
        }
    }

    public function toDQL(string $alias): ?string
    {
        $conditions = array_filter(
            array_map(
                fn(DoctrineSpecificationInterface $adapter) => $adapter->toDQL($alias),
                $this->adapters
            )
        );

        if (empty($conditions)) {
            return null;
        }

        return '(' . implode(' AND ', $conditions) . ')';
    }

    public function getParameters(): array
    {
        $params = [];
        foreach ($this->adapters as $adapter) {
            $params = array_merge($params, $adapter->getParameters());
        }
        return $params;
    }

    public function getJoins(): array
    {
        $joins = [];
        foreach ($this->adapters as $adapter) {
            $joins = array_merge($joins, $adapter->getJoins());
        }
        return array_unique($joins);
    }

    public static function key(): ?string
    {
        return null;
    }
}
