<?php

namespace App\Repository;

use App\Entity\Maintenance;
use App\Entity\Vehicle;
use App\Enum\MaintenanceStatusEnum;
use App\Enum\MaintenanceTypeEnum;
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
    public function findByFilters(
        ?int $vehicleId = null,
        ?MaintenanceTypeEnum $type = null,
        ?MaintenanceStatusEnum $status = null,
        ?string $query = null,
        string $sort = 'createdAt',
        string $direction = 'DESC',
    ): array
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

        if ($type !== null) {
            $qb
                ->andWhere('m.maintenanceType = :maintenanceType')
                ->setParameter('maintenanceType', $type);
        }

        if ($status !== null) {
            $qb
                ->andWhere('m.status = :status')
                ->setParameter('status', $status);
        }

        if ($query !== null && trim($query) !== '') {
            $qb
                ->andWhere('LOWER(v.name) LIKE :query OR LOWER(v.registration) LIKE :query OR LOWER(m.notes) LIKE :query')
                ->setParameter('query', '%' . strtolower(trim($query)) . '%');
        }

        $sortFields = [
            'createdAt' => 'm.createdAt',
            'performedAt' => 'm.performedAt',
            'plannedAt' => 'm.plannedAt',
            'mileage' => 'm.mileage',
            'vehicle' => 'v.name',
            'status' => 'm.status',
            'type' => 'm.maintenanceType',
        ];

        $sortField = $sortFields[$sort] ?? $sortFields['createdAt'];
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        return $qb
            ->orderBy($sortField, $direction)
            ->addOrderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Maintenance[]
     */
    public function findForVehicle(Vehicle $vehicle): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.vehicle = :vehicle')
            ->andWhere('m.isDeleted = :isDeleted')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('isDeleted', false)
            ->orderBy('m.performedAt', 'DESC')
            ->addOrderBy('m.plannedAt', 'DESC')
            ->addOrderBy('m.createdAt', 'DESC')
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
