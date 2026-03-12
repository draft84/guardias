<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TaskLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaskLog>
 */
class TaskLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskLog::class);
    }

    public function save(TaskLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TaskLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return TaskLog[]
     */
    public function findByDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.date = :date')
            ->setParameter('date', $date)
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TaskLog[]
     */
    public function findByUserAndDate(User $user, \DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.date = :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
