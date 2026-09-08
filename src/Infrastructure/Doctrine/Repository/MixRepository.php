<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Mix\Entity\Mix;
use App\Domain\Mix\Repository\MixRepositoryInterface;
use App\Domain\Mix\Specification\SpecificationInterface;
use App\Domain\Mix\ValueObject\MixId;
use App\Infrastructure\Doctrine\Entity\Mix as MixDoctrine;
use App\Infrastructure\Mix\Doctrine\Mapper\MixMapperInterface;
use App\Infrastructure\Mix\Doctrine\Specification\Factory\SpecificationAdapterFactory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MixRepository extends ServiceEntityRepository implements MixRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly MixMapperInterface $mapper,
        private readonly SpecificationAdapterFactory $adapterFactory,
    ) {
        parent::__construct($registry, MixDoctrine::class);
    }

    // ========================================
    // 1. БАЗОВЫЕ ОПЕРАЦИИ
    // ========================================

    public function save(Mix $mix): void
    {
        $entity = $this->mapper->toDoctrine($mix);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function delete(Mix $mix): void
    {
        $entity = $this->findDoctrineById($mix->getId());
        if ($entity !== null) {
            $this->getEntityManager()->remove($entity);
            $this->getEntityManager()->flush();
        }
    }

    // ========================================
    // 2. ПОИСК
    // ========================================

    public function findById(MixId $id): ?Mix
    {
        $entity = $this->findDoctrineById($id);
        return $entity !== null ? $this->mapper->toDomain($entity) : null;
    }

    public function findByUuid(string $uuid): ?Mix
    {
        $entity = $this->findOneBy(['uuid' => $uuid]);
        return $entity !== null ? $this->mapper->toDomain($entity) : null;
    }

    /**
     * @return Mix[]
     */
    public function findMatches(SpecificationInterface $specification): array
    {
        $adapter = $this->adapterFactory->create($specification);
        $qb = $this->createQueryBuilder('m');

        // Добавляем JOIN-ы
        foreach ($adapter->getJoins() as $join) {
            $qb->leftJoin($join, null, 'WITH');
        }

        // Добавляем WHERE
        $dql = $adapter->toDQL('m');
        if ($dql !== null) {
            $qb->where($dql);
        }

        // Добавляем параметры
        foreach ($adapter->getParameters() as $key => $value) {
            $qb->setParameter($key, $value);
        }

        $results = $qb->getQuery()->getResult();

        return array_map(
            fn(MixDoctrine $entity) => $this->mapper->toDomain($entity),
            $results
        );
    }

    public function countMatches(SpecificationInterface $specification): int
    {
        $adapter = $this->adapterFactory->create($specification);
        $qb = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)');

        // Добавляем JOIN-ы
        foreach ($adapter->getJoins() as $join) {
            $qb->leftJoin($join, null, 'WITH');
        }

        // Добавляем WHERE
        $dql = $adapter->toDQL('m');
        if ($dql !== null) {
            $qb->where($dql);
        }

        // Добавляем параметры
        foreach ($adapter->getParameters() as $key => $value) {
            $qb->setParameter($key, $value);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function exists(MixId $id): bool
    {
        return $this->findDoctrineById($id) !== null;
    }

    // ========================================
    // 3. ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    // ========================================

    private function findDoctrineById(MixId $id): ?MixDoctrine
    {
        return $this->findOneBy(['uuid' => $id->toString()]);
    }
}
