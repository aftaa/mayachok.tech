<?php

namespace App\Repository;

use App\Entity\Mix;
use App\Specification\SpecificationInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mix>
 */
class MixRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mix::class);
    }

    public function save(Mix $mix, bool $flush = true): void
    {
        $this->getEntityManager()->persist($mix);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Mix $mix, bool $flush = true): void
    {
        $this->getEntityManager()->remove($mix);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByUuid(string $uuid): ?Mix
    {
        return $this->createQueryBuilder('m')
            ->where('m.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Mix[]
     */
    public function findMatches(SpecificationInterface $specification): array
    {
        $qb = $this->createQueryBuilder('m');

        foreach ($specification->getJoins() as $join) {
            $parts = explode('.', $join);
            if (count($parts) === 2) {
                $alias = $parts[0];
                $field = $parts[1];
                $qb->leftJoin("{$alias}.{$field}", $field);
            }
        }

        $dql = $specification->toDQL('m');
        if ($dql) {
            $qb->andWhere($dql);
        }

        foreach ($specification->getParameters() as $key => $value) {
            $qb->setParameter($key, $value);
        }

        return $qb->getQuery()->getResult();
    }
}
