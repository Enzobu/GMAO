<?php

namespace App\ApiPlatform\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Address;
use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\MaintenancePart;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class UserOwnedResourceExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->applyFilters($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->applyFilters($queryBuilder, $queryNameGenerator, $resourceClass);
    }

    private function applyFilters(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
    ): void {
        $rootAlias = $queryBuilder->getRootAliases()[0];

        $this->excludeDeleted($queryBuilder, $resourceClass, $rootAlias);

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            $queryBuilder->andWhere('1 = 0');
            return;
        }

        $this->restrictToCurrentUser($queryBuilder, $queryNameGenerator, $resourceClass, $rootAlias, $user);
    }

    private function excludeDeleted(QueryBuilder $queryBuilder, string $resourceClass, string $rootAlias): void
    {
        if (!$this->entityManager->getClassMetadata($resourceClass)->hasField('isDeleted')) {
            return;
        }

        $queryBuilder
            ->andWhere(sprintf('%s.isDeleted = :api_is_deleted', $rootAlias))
            ->setParameter('api_is_deleted', false);
    }

    private function restrictToCurrentUser(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $rootAlias,
        User $user,
    ): void {
        match ($resourceClass) {
            User::class => null,
            Address::class => $this->restrictAddress($queryBuilder, $rootAlias, $user),
            Vehicle::class => null,
            Maintenance::class,
            VehicleInsurance::class,
            VehicleInspection::class => $this->restrictThroughVehicle($queryBuilder, $queryNameGenerator, $rootAlias, $user),
            MaintenancePart::class => $this->restrictMaintenancePart($queryBuilder, $queryNameGenerator, $rootAlias, $user),
            Part::class => null,
            Document::class => $this->restrictDocument($queryBuilder, $queryNameGenerator, $rootAlias, $user),
            default => null,
        };
    }

    private function restrictAddress(QueryBuilder $queryBuilder, string $rootAlias, User $user): void
    {
        $queryBuilder
            ->andWhere(sprintf('%s = :api_current_user_address', $rootAlias))
            ->setParameter('api_current_user_address', $user->getAddress());
    }

    private function restrictThroughVehicle(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $rootAlias,
        User $user,
    ): void {
        $vehicleAlias = $queryNameGenerator->generateJoinAlias('vehicle');

        $queryBuilder
            ->join(sprintf('%s.vehicle', $rootAlias), $vehicleAlias)
            ->andWhere(sprintf('%s.user = :api_current_user', $vehicleAlias))
            ->setParameter('api_current_user', $user);
    }

    private function restrictMaintenancePart(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $rootAlias,
        User $user,
    ): void {
        $maintenanceAlias = $queryNameGenerator->generateJoinAlias('maintenance');
        $vehicleAlias = $queryNameGenerator->generateJoinAlias('vehicle');

        $queryBuilder
            ->join(sprintf('%s.maintenance', $rootAlias), $maintenanceAlias)
            ->join(sprintf('%s.vehicle', $maintenanceAlias), $vehicleAlias)
            ->andWhere(sprintf('%s.user = :api_current_user', $vehicleAlias))
            ->setParameter('api_current_user', $user);
    }

    private function restrictDocument(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $rootAlias,
        User $user,
    ): void {
        $vehicleAlias = $queryNameGenerator->generateJoinAlias('vehicle');
        $insuranceAlias = $queryNameGenerator->generateJoinAlias('vehicleInsurance');
        $insuranceVehicleAlias = $queryNameGenerator->generateJoinAlias('insuranceVehicle');
        $inspectionAlias = $queryNameGenerator->generateJoinAlias('vehicleInspection');
        $inspectionVehicleAlias = $queryNameGenerator->generateJoinAlias('inspectionVehicle');
        $partAlias = $queryNameGenerator->generateJoinAlias('part');
        $partVehicleAlias = $queryNameGenerator->generateJoinAlias('partVehicle');
        $maintenanceAlias = $queryNameGenerator->generateJoinAlias('maintenance');
        $maintenanceVehicleAlias = $queryNameGenerator->generateJoinAlias('maintenanceVehicle');

        $queryBuilder
            ->leftJoin(sprintf('%s.vehicle', $rootAlias), $vehicleAlias)
            ->leftJoin(sprintf('%s.vehicleInsurance', $rootAlias), $insuranceAlias)
            ->leftJoin(sprintf('%s.vehicle', $insuranceAlias), $insuranceVehicleAlias)
            ->leftJoin(sprintf('%s.vehicleInspection', $rootAlias), $inspectionAlias)
            ->leftJoin(sprintf('%s.vehicle', $inspectionAlias), $inspectionVehicleAlias)
            ->leftJoin(sprintf('%s.part', $rootAlias), $partAlias)
            ->leftJoin(sprintf('%s.vehicles', $partAlias), $partVehicleAlias)
            ->leftJoin(sprintf('%s.maintenance', $rootAlias), $maintenanceAlias)
            ->leftJoin(sprintf('%s.vehicle', $maintenanceAlias), $maintenanceVehicleAlias)
            ->andWhere(sprintf(
                '%1$s.user = :api_current_user OR %2$s.user = :api_current_user OR %3$s.user = :api_current_user OR %4$s.user = :api_current_user OR %5$s.user = :api_current_user OR %6$s.user = :api_current_user',
                $vehicleAlias,
                $rootAlias,
                $insuranceVehicleAlias,
                $inspectionVehicleAlias,
                $partVehicleAlias,
                $maintenanceVehicleAlias,
            ))
            ->setParameter('api_current_user', $user);
    }
}
