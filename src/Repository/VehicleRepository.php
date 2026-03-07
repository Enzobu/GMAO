<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicle>
 */
class VehicleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicle::class);
    }

    /**
     * @return Vehicle[]
     */
    public function findAllNotDeleted(): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.isDeleted = :isDeleted')
            ->setParameter('isDeleted', false)
            ->orderBy('v.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.user = :user')
            ->setParameter('user', $user)
            ->orderBy('v.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Vehicle[]
     */
    public function findAllNotDeletedByUser(User $user): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.user = :user')
            ->andWhere('v.isDeleted = :isDeleted')
            ->setParameter('user', $user)
            ->setParameter('isDeleted', false)
            ->orderBy('v.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneNotDeletedById(int $id): ?Vehicle
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.id = :id')
            ->andWhere('v.isDeleted = :isDeleted')
            ->setParameter('id', $id)
            ->setParameter('isDeleted', false)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
