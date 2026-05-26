<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\VehicleInspection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** @implements ProcessorInterface<VehicleInspection, VehicleInspection|null> */
final readonly class VehicleInspectionStateProcessor implements ProcessorInterface
{
    public function __construct(private EntityManagerInterface $entityManager, private Security $security) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?VehicleInspection
    {
        if (!$data instanceof VehicleInspection) return null;

        if ($operation instanceof DeleteOperationInterface) {
            $data->setIsDeleted(true);
            $this->entityManager->flush();
            return null;
        }

        $user = $this->security->getUser();
        if (!$this->security->isGranted('ROLE_ADMIN') && (!$user instanceof User || $data->getVehicle()?->getUser() !== $user)) {
            throw new AccessDeniedHttpException('Vous ne pouvez modifier que les contrôles de vos véhicules.');
        }

        if ($data->getId() === null) $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
