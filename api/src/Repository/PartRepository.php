<?php

namespace App\Repository;

use App\Entity\Part;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Part>
 */
class PartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Part::class);
    }

    public function findByFilters(?int $vehicleId = null, ?int $partTypeId = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.vehicles', 'v')
            ->addSelect('v')
            ->leftJoin('p.partType', 'pt')
            ->addSelect('pt');

        if ($vehicleId !== null) {
            $qb
                ->andWhere('v.id = :vehicleId')
                ->setParameter('vehicleId', $vehicleId);
        }

        if ($partTypeId !== null) {
            $qb
                ->andWhere('pt.id = :partTypeId')
                ->setParameter('partTypeId', $partTypeId);
        }

        return $qb
            ->orderBy('p.quantity', 'ASC')
            ->addOrderBy('pt.name', 'ASC')
            ->addOrderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
