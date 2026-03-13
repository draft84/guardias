<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GuardAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GuardAssignment>
 */
class GuardAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuardAssignment::class);
    }

    public function save(GuardAssignment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GuardAssignment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return GuardAssignment[]
     */
    public function findByDate(\DateTimeInterface $date): array
    {
        // Convertir a string YYYY-MM-DD para comparar solo la fecha
        $dateString = $date->format('Y-m-d');
        
        return $this->createQueryBuilder('ga')
            ->where('ga.date = :date')
            ->setParameter('date', $dateString)
            ->orderBy('ga.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return GuardAssignment[]
     */
    public function findByUserAndDateRange(int $userId, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('ga')
            ->where('ga.user = :user')
            ->setParameter('user', $userId)
            ->andWhere('ga.date BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('ga.date', 'ASC')
            ->addOrderBy('ga.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return GuardAssignment[]
     */
    public function findByGuardAndUserAndDateRange(
        string $guardId,
        string $userId,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): array {
        return $this->createQueryBuilder('ga')
            ->where('ga.guard = :guard')
            ->setParameter('guard', $guardId)
            ->andWhere('ga.user = :user')
            ->setParameter('user', $userId)
            ->andWhere('ga.date BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('ga.date', 'ASC')
            ->addOrderBy('ga.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return GuardAssignment[]
     */
    public function findActiveAssignments(): array
    {
        return $this->createQueryBuilder('ga')
            ->where('ga.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }
}
