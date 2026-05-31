<?php

namespace App\Repository;

use App\Entity\VehicleInspection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VehicleInspection>
 */
class VehicleInspectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VehicleInspection::class);
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, string> $orderBy
     *
     * @return VehicleInspection[]
     */
    public function findByVehicle(array $criteria = [], array $orderBy = [], bool $deleted = false): array
    {
        $qb = $this->createVehicleInspectionQueryBuilder();

        $this->applyVehicleCriteria($qb, $criteria);

        if (!$deleted) {
            $this->excludeDeletedRows($qb);
        }

        $this->applyOrdering($qb, $orderBy);

        return $qb->getQuery()->getResult();
    }

    private function createVehicleInspectionQueryBuilder(): QueryBuilder
    {
        return $this
            ->createQueryBuilder('vi')
            ->innerJoin('vi.vehicle', 'v')
            ->addSelect('v')
        ;
    }

    /** @param array<string, mixed> $criteria */
    private function applyVehicleCriteria(QueryBuilder $qb, array $criteria): void
    {
        if (!array_key_exists('vehicle', $criteria) || $criteria['vehicle'] === null) {
            return;
        }

        $qb
            ->andWhere('vi.vehicle = :vehicle')
            ->setParameter('vehicle', $criteria['vehicle'])
        ;
    }

    private function excludeDeletedRows(QueryBuilder $qb): void
    {
        $qb
            ->andWhere('v.isDeleted = :deleted')
            ->setParameter('deleted', false)
            ->andWhere('vi.isDeleted = :deleted')
            ->setParameter('deleted', false)
        ;
    }

    /** @param array<string, string> $orderBy */
    private function applyOrdering(QueryBuilder $qb, array $orderBy): void
    {
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy('vi.' . $field, $this->normalizeDirection($direction));
        }
    }

    private function normalizeDirection(string $direction): string
    {
        return strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
    }
}
