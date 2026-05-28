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
        $queryBuilder = $this
            ->createQueryBuilder('vi')
            ->innerJoin('vi.vehicle', 'v')
            ->addSelect('v')
        ;

        $vehicle = $criteria['vehicle'] ?? null;
        if ($vehicle !== null) {
            $queryBuilder
                ->andWhere('vi.vehicle = :vehicle')
                ->setParameter('vehicle', $vehicle)
            ;
        }

        if (!$deleted) {
            foreach (['v', 'vi'] as $alias) {
                $queryBuilder->andWhere(sprintf('%s.isDeleted = :deleted', $alias));
            }

            $queryBuilder->setParameter('deleted', false);
        }

        foreach ($orderBy as $field => $direction) {
            $queryBuilder->addOrderBy('vi.' . $field, strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC');
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
