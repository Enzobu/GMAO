<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProcessorInterface<Vehicle, Vehicle|null>
 */
final readonly class VehicleStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Vehicle
    {
        if (!$data instanceof Vehicle) {
            return null;
        }

        if ($operation instanceof DeleteOperationInterface) {
            $data->setIsDeleted(true);
            $this->entityManager->flush();

            return null;
        }

        $user = $this->security->getUser();

        if ($data->getId() === null) {
            if ($user instanceof User && (!$this->security->isGranted('ROLE_ADMIN') || $data->getUser() === null)) {
                $data->setUser($user);
            }

            $this->entityManager->persist($data);
        }

        $this->entityManager->flush();

        return $data;
    }
}
