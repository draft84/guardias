<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Guard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Guard>
 */
class GuardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Guard::class);
    }

    public function save(Guard $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Guard $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Guard[]
     */
    public function findActiveGuards(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.active = :active')
            ->setParameter('active', true)
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Guard[]
     */
    public function findByDepartment(int $departmentId): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.department = :department')
            ->setParameter('department', $departmentId)
            ->andWhere('g.active = :active')
            ->setParameter('active', true)
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
