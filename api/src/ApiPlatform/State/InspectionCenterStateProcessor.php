<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\InspectionCenter;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @implements ProcessorInterface<InspectionCenter, InspectionCenter|null>
 */
final readonly class InspectionCenterStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?InspectionCenter
    {
        if (!$data instanceof InspectionCenter) {
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
