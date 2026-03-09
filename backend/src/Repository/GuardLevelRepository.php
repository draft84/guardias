<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GuardLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GuardLevel>
 *
 * @method GuardLevel|null find($id, $lockMode = null, $lockVersion = null)
 * @method GuardLevel|null findOneBy(array $criteria, array $orderBy = null)
 * @method GuardLevel[]    findAll()
 * @method GuardLevel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GuardLevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuardLevel::class);
    }
}
