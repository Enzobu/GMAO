<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\VehicleInsurance;

/** @implements ProcessorInterface<VehicleInsurance, VehicleInsurance|null> */
final readonly class VehicleInsuranceStateProcessor extends AbstractVehicleEventStateProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?VehicleInsurance
    {
        /** @var VehicleInsurance|null */
        return $this->processVehicleEvent(
            $data,
            $operation,
            VehicleInsurance::class,
            'Vous ne pouvez modifier que les assurances de vos véhicules.',
        );
    }
}
