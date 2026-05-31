<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MaintenanceType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProcessorInterface<MaintenanceType, MaintenanceType|null>
 */
final readonly class MaintenanceTypeStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?MaintenanceType
    {
        if (!$data instanceof MaintenanceType) {
            return null;
        }

        if ($operation instanceof DeleteOperationInterface) {
            $data->setIsDeleted(true);
            $this->entityManager->flush();

            return null;
        }

        if ($data->getId() === null) {
            $this->entityManager->persist($data);
        }

        $this->entityManager->flush();

        return $data;
    }
}
