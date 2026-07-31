<?php

namespace App\Specification;

readonly class OrSpecification implements SpecificationInterface
{
    /**
     * @param SpecificationInterface[] $specifications
     */
    public function __construct(
        private array $specifications
    ) {}

    public function toDQL(string $alias): ?string
    {
        $conditions = array_map(
            fn(SpecificationInterface $spec) => $spec->toDQL($alias),
            $this->specifications
        );

        // Убираем пустые условия
        $conditions = array_filter($conditions, fn($c) => $c !== null);

        if (empty($conditions)) {
            return null;
        }

        return '(' . implode(' OR ', $conditions) . ')';
    }

    public function getParameters(): array
    {
        $params = [];
        foreach ($this->specifications as $spec) {
            $params = array_merge($params, $spec->getParameters());
        }
        return $params;
    }

    public function getJoins(): array
    {
        $joins = [];
        foreach ($this->specifications as $spec) {
            $joins = array_merge($joins, $spec->getJoins());
        }
        return array_unique($joins);
    }
}
