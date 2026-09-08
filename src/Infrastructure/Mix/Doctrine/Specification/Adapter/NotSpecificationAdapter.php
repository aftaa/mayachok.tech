<?php

declare(strict_types=1);

namespace App\Infrastructure\Mix\Doctrine\Specification\Adapter;

use App\Domain\Mix\Specification\NotSpecification;
use App\Infrastructure\Mix\Doctrine\Specification\Contract\DoctrineSpecificationInterface;
use App\Infrastructure\Mix\Doctrine\Specification\Factory\SpecificationAdapterFactory;

final class NotSpecificationAdapter implements DoctrineSpecificationInterface
{
    private DoctrineSpecificationInterface $adapter;

    public function __construct(
        private readonly NotSpecification $specification,
        SpecificationAdapterFactory $factory,
    ) {
        $this->adapter = $factory->create($specification->getSpecification());
    }

    public function toDQL(string $alias): ?string
    {
        $dql = $this->adapter->toDQL($alias);
        return $dql !== null ? sprintf('NOT (%s)', $dql) : null;
    }

    public function getParameters(): array
    {
        return $this->adapter->getParameters();
    }

    public function getJoins(): array
    {
        return $this->adapter->getJoins();
    }

    public static function key(): ?string
    {
        return null;
    }
}
