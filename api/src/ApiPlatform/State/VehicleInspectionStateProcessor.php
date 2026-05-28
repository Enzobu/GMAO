<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\VehicleInspection;

/** @implements ProcessorInterface<VehicleInspection, VehicleInspection|null> */
final readonly class VehicleInspectionStateProcessor extends AbstractVehicleEventStateProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?VehicleInspection
    {
        /** @var VehicleInspection|null */
        return $this->processVehicleEvent(
            $data,
            $operation,
            VehicleInspection::class,
            'Vous ne pouvez modifier que les contrôles de vos véhicules.',
        );
    }
}
