<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    public function findByUser(User $user, bool $deleted = false): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :user')
            ->setParameter('user', $user)
            ->andWhere('d.isDeleted = :deleted')
            ->setParameter('deleted', $deleted)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByVehicle(Vehicle $vehicle, bool $deleted = false): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.vehicle = :vehicle')
            ->setParameter('vehicle', $vehicle)
            ->andWhere('d.isDeleted = :deleted')
            ->setParameter('deleted', $deleted)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByVehicleInspection(VehicleInspection $vehicleInspection, bool $deleted = false): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.vehicleInspection = :vehicleInspection')
            ->setParameter('vehicleInspection', $vehicleInspection)
            ->andWhere('d.isDeleted = :deleted')
            ->setParameter('deleted', $deleted)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByVehicleInsurance(VehicleInsurance $vehicleInsurance, bool $deleted = false): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.vehicleInsurance = :vehicleInsurance')
            ->setParameter('vehicleInsurance', $vehicleInsurance)
            ->andWhere('d.isDeleted = :deleted')
            ->setParameter('deleted', $deleted)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByPart(Part $part, bool $deleted = false): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.part = :part')
            ->setParameter('part', $part)
            ->andWhere('d.isDeleted = :deleted')
            ->setParameter('deleted', $deleted)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
