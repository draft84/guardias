<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShiftSwapRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShiftSwapRequest>
 */
class ShiftSwapRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShiftSwapRequest::class);
    }

    public function save(ShiftSwapRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ShiftSwapRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return ShiftSwapRequest[]
     */
    public function findPendingRequests(): array
    {
        return $this->createQueryBuilder('ssr')
            ->where('ssr.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('ssr.requestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ShiftSwapRequest[]
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('ssr')
            ->where('ssr.requestedBy = :user')
            ->setParameter('user', $userId)
            ->orderBy('ssr.requestedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
