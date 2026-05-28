<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract readonly class AbstractVehicleEventStateProcessor
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {}

    /** @param class-string $supportedClass */
    protected function processVehicleEvent(mixed $data, Operation $operation, string $supportedClass, string $accessDeniedMessage): ?object
    {
        if (!$data instanceof $supportedClass) {
            return null;
        }

        if ($operation instanceof DeleteOperationInterface) {
            $data->setIsDeleted(true);
            $this->entityManager->flush();

            return null;
        }

        $this->denyUnlessAdminOrOwner($data, $accessDeniedMessage);

        if ($data->getId() === null) {
            $this->entityManager->persist($data);
        }

        $this->entityManager->flush();

        return $data;
    }

    private function denyUnlessAdminOrOwner(object $data, string $message): void
    {
        $user = $this->security->getUser();

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if (!$user instanceof User || $data->getVehicle()?->getUser() !== $user) {
            throw new AccessDeniedHttpException($message);
        }
    }
}
