<?php

namespace App\Repository;

use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleInsurance>
 */
class VehicleInsuranceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleInsurance::class);
    }

    public function deactivateAllForVehicle(Vehicle $vehicle): int
    {
        return $this->createQueryBuilder('vi')
            ->update()
            ->set('vi.isActive', ':false')
            ->where('vi.vehicle = :vehicle')
            ->andWhere('vi.isActive = :true')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('true', true)
            ->setParameter('false', false)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, string> $orderBy
     *
     * @return VehicleInsurance[]
     */
    public function findByVehicle(array $criteria = [], array $orderBy = [], bool $deleted = false): array
    {
        $qb = $this
            ->createQueryBuilder('vi')
            ->innerJoin('vi.vehicle', 'v')
            ->addSelect('v')
        ;

        if (array_key_exists('vehicle', $criteria) && $criteria['vehicle'] !== null) {
            $qb
                ->andWhere('vi.vehicle = :vehicle')
                ->setParameter('vehicle', $criteria['vehicle'])
            ;
        }

        if ($deleted === false) {
            $qb
                ->andWhere('v.isDeleted = :deleted')
                ->setParameter('deleted', false)
            ;
        }

        foreach ($orderBy as $field => $direction) {
            $allowedDirection = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
            $qb
                ->addOrderBy('vi.' . $field, $allowedDirection)
            ;
        }

        return $qb->getQuery()->getResult();
    }
}
