<?php

namespace App\Service;

use App\Entity\Maintenance;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class VehicleManager
{
    public const FORCE_MILEAGE_FIELD = 'force_mileage';

    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {}
    
    public function isAuthorized(User $user, Vehicle $vehicle): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($vehicle->getUser() === $user) {
            return true;
        }

        return false;
    }

    /**
     * @return array{currentMileage:int, submittedMileage:int, fieldError:string}|null
     */
    public function buildEventMileageWarning(
        ?Vehicle $oldVehicle,
        ?int $oldMileage,
        ?Vehicle $newVehicle,
        ?int $newMileage,
    ): ?array {
        $warning = null;

        if ($this->shouldWarnAboutOldVehicleMileage($oldVehicle, $oldMileage, $newVehicle, $newMileage)) {
            return $this->buildMileageWarning($oldMileage, $newMileage ?? $oldMileage);
        }

        if ($this->shouldCheckNewVehicleMileage($oldVehicle, $oldMileage, $newVehicle, $newMileage)) {
            $currentMileage = $newVehicle->getLastMileage();

            if ($currentMileage !== null && $newMileage < $currentMileage) {
                $warning = $this->buildMileageWarning($currentMileage, $newMileage);
            }
        }

        return $warning;
    }

    private function shouldWarnAboutOldVehicleMileage(
        ?Vehicle $oldVehicle,
        ?int $oldMileage,
        ?Vehicle $newVehicle,
        ?int $newMileage,
    ): bool {
        return $oldVehicle !== null
            && $oldMileage !== null
            && $oldMileage === $oldVehicle->getLastMileage()
            && (
                !$this->isSameVehicle($oldVehicle, $newVehicle)
                || $newMileage === null
                || $newMileage < $oldMileage
            );
    }

    private function shouldCheckNewVehicleMileage(
        ?Vehicle $oldVehicle,
        ?int $oldMileage,
        ?Vehicle $newVehicle,
        ?int $newMileage,
    ): bool {
        return $newVehicle !== null
            && $newMileage !== null
            && !($this->isSameVehicle($oldVehicle, $newVehicle) && $oldMileage === $newMileage);
    }

    /**
     * @return array{currentMileage:int, submittedMileage:int, fieldError:string}|null
     */
    public function buildVehicleMileageWarning(?int $oldMileage, ?int $newMileage): ?array
    {
        if ($newMileage === null || $oldMileage === null || $oldMileage === $newMileage) {
            return null;
        }

        if ($newMileage >= $oldMileage) {
            return null;
        }

        return $this->buildMileageWarning($oldMileage, $newMileage);
    }

    public function syncAfterEventMileageChange(
        ?Vehicle $oldVehicle,
        ?int $oldMileage,
        ?Vehicle $newVehicle,
        ?int $newMileage,
        ?int $oldVehicleLastMileage,
    ): bool {
        $changed = false;

        if (
            $oldVehicle !== null
            && $oldMileage !== null
            && $oldVehicleLastMileage !== null
            && $oldMileage === $oldVehicleLastMileage
            && (!$this->isSameVehicle($oldVehicle, $newVehicle) || $newMileage === null || $newMileage < $oldMileage)
        ) {
            $changed = $this->recalculateMileageFromHistory($oldVehicle) || $changed;
        }

        if ($newVehicle !== null && $newMileage !== null) {
            $currentMileage = $newVehicle->getLastMileage();

            if ($currentMileage === null || $newMileage > $currentMileage) {
                $newVehicle->setLastMileage($newMileage);
                $changed = true;
            }
        }

        return $changed;
    }

    private function recalculateMileageFromHistory(Vehicle $vehicle): bool
    {
        $highestMileage = $this->findHighestMileageFromHistory($vehicle);

        if ($vehicle->getLastMileage() === $highestMileage) {
            return false;
        }

        $vehicle->setLastMileage($highestMileage);

        return true;
    }

    private function findHighestMileageFromHistory(Vehicle $vehicle): ?int
    {
        $maintenanceMileage = $this->entityManager->createQueryBuilder()
            ->select('MAX(m.mileage)')
            ->from(Maintenance::class, 'm')
            ->andWhere('m.vehicle = :vehicle')
            ->andWhere('m.isDeleted = :isDeleted')
            ->andWhere('m.finishedAt IS NOT NULL')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('isDeleted', false)
            ->getQuery()
            ->getSingleScalarResult();

        $inspectionMileage = $this->entityManager->createQueryBuilder()
            ->select('MAX(vi.mileage)')
            ->from(VehicleInspection::class, 'vi')
            ->andWhere('vi.vehicle = :vehicle')
            ->andWhere('vi.isDeleted = :isDeleted')
            ->andWhere('vi.mileage IS NOT NULL')
            ->setParameter('vehicle', $vehicle)
            ->setParameter('isDeleted', false)
            ->getQuery()
            ->getSingleScalarResult();

        $mileages = array_filter(
            [$maintenanceMileage, $inspectionMileage],
            static fn (mixed $mileage): bool => $mileage !== null,
        );

        if ($mileages === []) {
            return null;
        }

        return max(array_map('intval', $mileages));
    }

    /**
     * @return array{currentMileage:int, submittedMileage:int, fieldError:string}
     */
    private function buildMileageWarning(int $currentMileage, int $submittedMileage): array
    {
        return [
            'currentMileage' => $currentMileage,
            'submittedMileage' => $submittedMileage,
            'fieldError' => sprintf(
                'Le kilométrage doit être supérieur au dernier kilométrage connu du véhicule (%s km).',
                number_format($currentMileage, 0, ',', ' '),
            ),
        ];
    }

    private function isSameVehicle(?Vehicle $first, ?Vehicle $second): bool
    {
        if ($first === null || $second === null) {
            return false;
        }

        if ($first === $second) {
            return true;
        }

        return $first->getId() !== null && $first->getId() === $second->getId();
    }
}
