<?php

namespace App\Service;

use App\Entity\Part;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class PartCompatibilityGuard
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function assertNoOverlap(Part $part): void
    {
        $type = $part->getPartType();
        if ($type === null) {
            return;
        }

        $vehicleIds = [];
        foreach ($part->getVehicles() as $vehicle) {
            if ($vehicle->getId() !== null) {
                $vehicleIds[] = $vehicle->getId();
            }
        }

        if ($vehicleIds === []) {
            return;
        }

        $query = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT existing.id')
            ->from(Part::class, 'existing')
            ->join('existing.vehicles', 'vehicle')
            ->where('existing.partType = :partType')
            ->andWhere('existing.isDeleted = false')
            ->andWhere('vehicle.id IN (:vehicleIds)')
            ->setParameter('partType', $type)
            ->setParameter('vehicleIds', $vehicleIds);

        if ($part->getId() !== null) {
            $query->andWhere('existing.id != :currentId')
                ->setParameter('currentId', $part->getId());
        }

        if ($query->setMaxResults(1)->getQuery()->getOneOrNullResult() !== null) {
            throw new ConflictHttpException(
                'Ce type de pièce existe déjà en stock pour au moins un des véhicules sélectionnés.'
            );
        }
    }
}
