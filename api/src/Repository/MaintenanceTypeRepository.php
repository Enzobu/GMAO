<?php

namespace App\Repository;

use App\Entity\MaintenanceType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaintenanceType>
 */
class MaintenanceTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaintenanceType::class);
    }

    /**
     * @return MaintenanceType[]
     */
    public function findAllNotDeleted(): array
    {
        return $this->createQueryBuilder('mt')
            ->andWhere('mt.isDeleted = :isDeleted')
            ->setParameter('isDeleted', false)
            ->orderBy('mt.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
