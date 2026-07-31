<?php

namespace App\Repository;

use App\Entity\Mix;
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

    /**
     * @return list<Mix>
     */
    public function findPublic(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.isPrivate = false')
            ->getQuery()
            ->getResult();
    }
}
