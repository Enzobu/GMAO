<?php

namespace App\Repository;

use App\Entity\Maintenance;
use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Maintenance>
 */
class MaintenanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Maintenance::class);
    }

    /**
     * @return Maintenance[]
     */
    public function findByFilters(?int $vehicleId = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->join('m.vehicle', 'v')
            ->addSelect('v')
            ->andWhere('m.isDeleted = :isDeleted')
            ->setParameter('isDeleted', false);

        if ($vehicleId !== null) {
            $qb
                ->andWhere('v.id = :vehicleId')
                ->setParameter('vehicleId', $vehicleId);
        }

        return $qb
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestPerformedByVehicle(Vehicle $vehicle): ?Maintenance
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.vehicle = :vehicle')
            ->andWhere('m.isDeleted = :isDeleted')
            ->andWhere('m.performedAt IS NOT NULL')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('isDeleted', false)
            ->orderBy('m.performedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
