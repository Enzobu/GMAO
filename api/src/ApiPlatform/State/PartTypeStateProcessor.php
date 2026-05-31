<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\PartType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * @implements ProcessorInterface<PartType, PartType|null>
 */
final readonly class PartTypeStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?PartType
    {
        if (!$data instanceof PartType) {
            return null;
        }

        if ($operation instanceof DeleteOperationInterface) {
            if (!$data->getParts()->isEmpty()) {
                throw new ConflictHttpException('Impossible de supprimer ce type : des pièces utilisent encore ce type.');
            }

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
