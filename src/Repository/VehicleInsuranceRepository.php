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
}
