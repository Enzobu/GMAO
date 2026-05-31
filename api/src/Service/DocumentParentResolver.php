<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DocumentParentResolver
{
    /** @var array<string, class-string> */
    private const PARENT_CLASSES = [
        'users' => User::class,
        'vehicles' => Vehicle::class,
        'vehicle_insurances' => VehicleInsurance::class,
        'vehicle_inspections' => VehicleInspection::class,
        'maintenances' => Maintenance::class,
        'parts' => Part::class,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentRepository $documentRepository,
    ) {}

    public function resolve(string $parentType, int $parentId): User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part|null
    {
        $class = self::PARENT_CLASSES[$parentType] ?? null;

        if ($class === null) {
            return null;
        }

        $parent = $this->entityManager->find($class, $parentId);

        return $parent instanceof User
            || $parent instanceof Vehicle
            || $parent instanceof VehicleInsurance
            || $parent instanceof VehicleInspection
            || $parent instanceof Maintenance
            || $parent instanceof Part
            ? $parent
            : null;
    }

    /** @return list<Document> */
    public function documents(User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part $parent): array
    {
        return match (true) {
            $parent instanceof User => $this->documentRepository->findByUser($parent),
            $parent instanceof Vehicle => $this->documentRepository->findByVehicle($parent),
            $parent instanceof VehicleInsurance => $this->documentRepository->findByVehicleInsurance($parent),
            $parent instanceof VehicleInspection => $this->documentRepository->findByVehicleInspection($parent),
            $parent instanceof Maintenance => $this->documentRepository->findByMaintenance($parent),
            $parent instanceof Part => $this->documentRepository->findByPart($parent),
        };
    }

    public function belongsToParent(Document $document, User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part $parent): bool
    {
        return match (true) {
            $parent instanceof User => $this->sameEntity($document->getUser(), $parent),
            $parent instanceof Vehicle => $this->sameEntity($document->getVehicle(), $parent),
            $parent instanceof VehicleInsurance => $this->sameEntity($document->getVehicleInsurance(), $parent),
            $parent instanceof VehicleInspection => $this->sameEntity($document->getVehicleInspection(), $parent),
            $parent instanceof Maintenance => $this->sameEntity($document->getMaintenance(), $parent),
            $parent instanceof Part => $this->sameEntity($document->getPart(), $parent),
        };
    }

    public function attach(Document $document, User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part $parent): void
    {
        match (true) {
            $parent instanceof User => $document->setUser($parent),
            $parent instanceof Vehicle => $document->setVehicle($parent),
            $parent instanceof VehicleInsurance => $document->setVehicleInsurance($parent),
            $parent instanceof VehicleInspection => $document->setVehicleInspection($parent),
            $parent instanceof Maintenance => $document->setMaintenance($parent),
            $parent instanceof Part => $document->setPart($parent),
        };
    }

    public function owningVehicle(User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part $parent): ?Vehicle
    {
        return match (true) {
            $parent instanceof Vehicle => $parent,
            $parent instanceof VehicleInsurance => $parent->getVehicle(),
            $parent instanceof VehicleInspection => $parent->getVehicle(),
            $parent instanceof Maintenance => $parent->getVehicle(),
            default => null,
        };
    }

    public function isDeleted(User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part $parent): bool
    {
        return match (true) {
            $parent instanceof User => $parent->isDeleted(),
            $parent instanceof Vehicle => $parent->isDeleted(),
            $parent instanceof VehicleInsurance => $parent->isDeleted(),
            $parent instanceof VehicleInspection => $parent->isDeleted(),
            $parent instanceof Maintenance => $parent->isDeleted(),
            $parent instanceof Part => $parent->isDeleted(),
        };
    }

    private function sameEntity(?object $candidate, object $expected): bool
    {
        if ($candidate === null) {
            return false;
        }

        if ($candidate === $expected) {
            return true;
        }

        return method_exists($candidate, 'getId')
            && method_exists($expected, 'getId')
            && $candidate->getId() !== null
            && $candidate->getId() === $expected->getId();
    }
}
