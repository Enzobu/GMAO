<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Part;
use App\Service\PartCompatibilityGuard;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** @implements ProcessorInterface<Part, Part|null> */
final readonly class PartStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PartCompatibilityGuard $compatibilityGuard,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?Part
    {
        if (!$data instanceof Part) {
            return null;
        }

        return $this->entityManager->wrapInTransaction(function () use ($data, $operation): ?Part {
            if ($operation instanceof DeleteOperationInterface) {
                $this->deletePart($data);

                return null;
            }

            $type = $data->getPartType();
            if ($type === null || $type->isDeleted()) {
                throw new ConflictHttpException('Sélectionnez un type de pièce actif.');
            }

            // Serialize concurrent creations for the same type before checking overlaps.
            $this->entityManager->lock($type, LockMode::PESSIMISTIC_WRITE);
            $this->compatibilityGuard->assertNoOverlap($data);

            if ($data->getId() === null) {
                $this->entityManager->persist($data);
            }

            $this->entityManager->flush();

            return $data;
        });
    }

    private function deletePart(Part $part): void
    {
        if (!$part->getMaintenanceParts()->isEmpty() || !$part->getDocuments()->isEmpty()) {
            $part->setIsDeleted(true);
        } else {
            $this->entityManager->remove($part);
        }

        $this->entityManager->flush();
    }
}
