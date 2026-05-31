<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Maintenance;
use App\Entity\User;
use App\Enum\MaintenanceStatusEnum;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** @implements ProcessorInterface<Maintenance, Maintenance|null> */
final readonly class MaintenanceStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private VehicleManager $vehicleManager,
        private RequestStack $requestStack,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Maintenance
    {
        if (!$data instanceof Maintenance) {
            return null;
        }

        $this->denyUnlessAllowed($data, $operation);

        $previousMaintenance = $context['previous_data'] ?? null;
        $oldVehicle = $previousMaintenance instanceof Maintenance ? $previousMaintenance->getVehicle() : null;
        $oldMileage = $previousMaintenance instanceof Maintenance ? $this->getMileageContribution($previousMaintenance) : null;
        $oldVehicleLastMileage = $oldVehicle?->getLastMileage();
        $oldStockMap = $previousMaintenance instanceof Maintenance ? $this->stockMap($previousMaintenance) : [];

        if ($operation instanceof DeleteOperationInterface) {
            $data->setIsDeleted(true);
            $this->restoreStock($oldStockMap);
            $this->entityManager->flush();

            if ($this->vehicleManager->syncAfterEventMileageChange($oldVehicle, $oldMileage, null, null, $oldVehicleLastMileage)) {
                $this->entityManager->flush();
            }

            return null;
        }

        $this->normalizeCompletionFields($data);
        $this->guardCompletedMileage($data);
        $this->guardCompatibleParts($data);
        $this->guardMileage($oldVehicle, $oldMileage, $data);
        $newStockMap = $data->getFinishedAt() !== null ? $this->stockMap($data) : [];
        $this->applyStockDelta($oldStockMap, $newStockMap);

        foreach ($data->getMaintenanceParts() as $maintenancePart) {
            $maintenancePart->setMaintenance($data);
        }

        if ($data->getId() === null) {
            $this->entityManager->persist($data);
        }

        $this->entityManager->flush();

        if ($this->vehicleManager->syncAfterEventMileageChange($oldVehicle, $oldMileage, $data->getVehicle(), $this->getMileageContribution($data), $oldVehicleLastMileage)) {
            $this->entityManager->flush();
        }

        return $data;
    }

    private function denyUnlessAllowed(Maintenance $maintenance, Operation $operation): void
    {
        if ($operation instanceof DeleteOperationInterface) {
            if (!$this->security->isGranted('ROLE_ADMIN')) {
                throw new AccessDeniedHttpException('Seul un administrateur peut supprimer une intervention.');
            }

            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || $maintenance->getVehicle()?->getUser() !== $user) {
            throw new AccessDeniedHttpException('Vous ne pouvez modifier que les interventions de vos véhicules.');
        }
    }

    private function guardMileage(?\App\Entity\Vehicle $oldVehicle, ?int $oldMileage, Maintenance $maintenance): void
    {
        $warning = $this->vehicleManager->buildEventMileageWarning(
            oldVehicle: $oldVehicle,
            oldMileage: $oldMileage,
            newVehicle: $maintenance->getVehicle(),
            newMileage: $this->getMileageContribution($maintenance),
        );

        if ($warning === null) {
            return;
        }

        $forceMileage = $this->requestStack->getCurrentRequest()?->query->getBoolean('forceMileage') ?? false;
        if ($forceMileage && $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        throw new ConflictHttpException($warning['fieldError']);
    }

    private function guardCompletedMileage(Maintenance $maintenance): void
    {
        if ($maintenance->getStatus() === MaintenanceStatusEnum::Completed && $maintenance->getFinishedAt() === null) {
            throw new BadRequestHttpException('La date de fin est obligatoire pour terminer une intervention.');
        }

        if ($maintenance->getStatus() === MaintenanceStatusEnum::Completed && $maintenance->getMileage() === null) {
            throw new BadRequestHttpException('Le kilométrage est obligatoire pour terminer une intervention.');
        }
    }

    private function normalizeCompletionFields(Maintenance $maintenance): void
    {
        if ($maintenance->getStatus() === MaintenanceStatusEnum::Completed) {
            return;
        }

        $maintenance->setFinishedAt(null);
        $maintenance->setMileage(null);
    }

    private function guardCompatibleParts(Maintenance $maintenance): void
    {
        $vehicle = $maintenance->getVehicle();
        if ($vehicle === null) {
            return;
        }

        foreach ($maintenance->getMaintenanceParts() as $maintenancePart) {
            $part = $maintenancePart->getPart();
            if ($part === null) {
                throw new BadRequestHttpException('Chaque ligne de pièce doit contenir une pièce valide.');
            }

            $isCompatible = $part->getVehicles()->exists(
                static fn (int $key, \App\Entity\Vehicle $compatibleVehicle): bool => $compatibleVehicle->getId() === $vehicle->getId()
            );

            if (!$isCompatible) {
                throw new BadRequestHttpException(sprintf(
                    'La pièce %s n’est pas compatible avec ce véhicule.',
                    $part->getPartType()?->getName() ?? 'sélectionnée'
                ));
            }
        }
    }

    /** @param array<int, int> $oldStockMap @param array<int, int> $newStockMap */
    private function applyStockDelta(array $oldStockMap, array $newStockMap): void
    {
        $partIds = array_unique([...array_keys($oldStockMap), ...array_keys($newStockMap)]);

        foreach ($partIds as $partId) {
            $delta = ($newStockMap[$partId] ?? 0) - ($oldStockMap[$partId] ?? 0);
            if ($delta === 0) {
                continue;
            }

            $part = $this->entityManager->find(\App\Entity\Part::class, $partId);
            if ($part === null) {
                continue;
            }

            $newQuantity = ($part->getQuantity() ?? 0) - $delta;
            if ($newQuantity < 0) {
                throw new ConflictHttpException(sprintf('Stock insuffisant pour %s.', $part->getPartType()?->getName() ?? 'la pièce sélectionnée'));
            }

            $part->setQuantity($newQuantity);
        }
    }

    /** @param array<int, int> $stockMap */
    private function restoreStock(array $stockMap): void
    {
        foreach ($stockMap as $partId => $quantity) {
            $part = $this->entityManager->find(\App\Entity\Part::class, $partId);
            if ($part !== null) {
                $part->setQuantity(($part->getQuantity() ?? 0) + $quantity);
            }
        }
    }

    /** @return array<int, int> */
    private function stockMap(Maintenance $maintenance): array
    {
        $map = [];

        if ($maintenance->getFinishedAt() === null) {
            return $map;
        }

        foreach ($maintenance->getMaintenanceParts() as $maintenancePart) {
            $partId = $maintenancePart->getPart()?->getId();
            if ($partId === null) {
                continue;
            }

            $map[$partId] = ($map[$partId] ?? 0) + ($maintenancePart->getQuantity() ?? 0);
        }

        return $map;
    }

    private function getMileageContribution(Maintenance $maintenance): ?int
    {
        return $maintenance->getFinishedAt() !== null ? $maintenance->getMileage() : null;
    }
}
