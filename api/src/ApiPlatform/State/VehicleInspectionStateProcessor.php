<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\VehicleInspection;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** @implements ProcessorInterface<VehicleInspection, VehicleInspection|null> */
final readonly class VehicleInspectionStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private VehicleManager $vehicleManager,
        private RequestStack $requestStack,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?VehicleInspection
    {
        if (!$data instanceof VehicleInspection) {
            return null;
        }

        $previousInspection = $context['previous_data'] ?? null;
        $oldVehicle = $previousInspection instanceof VehicleInspection ? $previousInspection->getVehicle() : null;
        $oldMileage = $previousInspection instanceof VehicleInspection ? $previousInspection->getMileage() : null;
        $oldVehicleLastMileage = $oldVehicle?->getLastMileage();

        if ($operation instanceof DeleteOperationInterface) {
            $data->setIsDeleted(true);
            $this->entityManager->flush();

            if ($this->vehicleManager->syncAfterEventMileageChange($oldVehicle, $oldMileage, null, null, $oldVehicleLastMileage)) {
                $this->entityManager->flush();
            }

            return null;
        }

        $this->denyUnlessAdminOrOwner($data);
        $this->guardMileage($oldVehicle, $oldMileage, $data);

        if ($data->getId() === null) {
            $this->entityManager->persist($data);
        }

        $this->entityManager->flush();

        if ($this->vehicleManager->syncAfterEventMileageChange($oldVehicle, $oldMileage, $data->getVehicle(), $data->getMileage(), $oldVehicleLastMileage)) {
            $this->entityManager->flush();
        }

        return $data;
    }

    private function denyUnlessAdminOrOwner(VehicleInspection $inspection): void
    {
        $user = $this->security->getUser();

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if (!$user instanceof User || $inspection->getVehicle()?->getUser() !== $user) {
            throw new AccessDeniedHttpException('Vous ne pouvez modifier que les contrôles de vos véhicules.');
        }
    }

    private function guardMileage(?\App\Entity\Vehicle $oldVehicle, ?int $oldMileage, VehicleInspection $inspection): void
    {
        $warning = $this->vehicleManager->buildEventMileageWarning(
            oldVehicle: $oldVehicle,
            oldMileage: $oldMileage,
            newVehicle: $inspection->getVehicle(),
            newMileage: $inspection->getMileage(),
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
}
