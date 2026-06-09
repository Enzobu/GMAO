<?php

namespace App\ApiPlatform\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class ListQueryExtension implements QueryCollectionExtensionInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
    ) {}

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $params = $request->query->all();

        match ($resourceClass) {
            Vehicle::class => $this->vehicles($queryBuilder, $rootAlias, $params),
            Part::class => $this->parts($queryBuilder, $rootAlias, $params),
            User::class => $this->users($queryBuilder, $rootAlias, $params),
            Maintenance::class => $this->maintenances($queryBuilder, $rootAlias, $params),
            VehicleInspection::class => $this->inspections($queryBuilder, $rootAlias, $params),
            VehicleInsurance::class => $this->insurances($queryBuilder, $rootAlias, $params),
            default => null,
        };
    }

    /** @param array<string, mixed> $params */
    private function vehicles(QueryBuilder $queryBuilder, string $rootAlias, array $params): void
    {
        $this->join($queryBuilder, $rootAlias, 'user', 'vehicleUser');
        $this->search($queryBuilder, $rootAlias, $params, [
            'name',
            'registration',
            'brand',
            'model',
            'vin',
            'engine',
        ], ['vehicleUser.email', 'vehicleUser.firstname', 'vehicleUser.lastname']);
        $this->equals($queryBuilder, $rootAlias, $params, 'type');
        $this->equals($queryBuilder, $rootAlias, $params, 'status');
        $this->vehicleEditability($queryBuilder, $rootAlias, $params);

        match ($this->string($params, 'sort')) {
            'registration' => $queryBuilder->orderBy($rootAlias.'.registration', 'ASC'),
            'year-desc' => $queryBuilder->orderBy($rootAlias.'.year', 'DESC'),
            'mileage-desc' => $queryBuilder->orderBy($rootAlias.'.lastMileage', 'DESC'),
            default => $queryBuilder->orderBy($rootAlias.'.name', 'ASC'),
        };
    }

    /** @param array<string, mixed> $params */
    private function parts(QueryBuilder $queryBuilder, string $rootAlias, array $params): void
    {
        $queryBuilder->distinct();
        $this->join($queryBuilder, $rootAlias, 'partType', 'partType');
        $this->join($queryBuilder, $rootAlias, 'vehicles', 'partVehicle');
        $this->search($queryBuilder, $rootAlias, $params, ['note'], [
            'partType.name',
            'partType.description',
            'partVehicle.name',
            'partVehicle.registration',
        ]);
        $this->equals($queryBuilder, 'partType', $params, 'partType');
        $this->equals($queryBuilder, 'partVehicle', $params, 'vehicle');
        $this->stock($queryBuilder, $rootAlias, $params);

        match ($this->string($params, 'sort')) {
            'name' => $queryBuilder->orderBy('partType.name', 'ASC'),
            'quantity-desc' => $queryBuilder->orderBy($rootAlias.'.quantity', 'DESC'),
            'updated-desc' => $queryBuilder->orderBy($rootAlias.'.updatedAt', 'DESC'),
            default => $queryBuilder->orderBy($rootAlias.'.quantity', 'ASC'),
        };
    }

    /** @param array<string, mixed> $params */
    private function users(QueryBuilder $queryBuilder, string $rootAlias, array $params): void
    {
        $this->search($queryBuilder, $rootAlias, $params, [
            'email',
            'firstname',
            'lastname',
        ]);
        $this->role($queryBuilder, $rootAlias, $params);
        $this->userEditability($queryBuilder, $rootAlias, $params);

        match ($this->string($params, 'sort')) {
            'email' => $queryBuilder->orderBy($rootAlias.'.email', 'ASC'),
            'role' => $queryBuilder->orderBy($rootAlias.'.roles', 'ASC'),
            default => $queryBuilder
                ->orderBy($rootAlias.'.lastname', 'ASC')
                ->addOrderBy($rootAlias.'.firstname', 'ASC'),
        };
    }

    /** @param array<string, mixed> $params */
    private function maintenances(QueryBuilder $queryBuilder, string $rootAlias, array $params): void
    {
        $this->join($queryBuilder, $rootAlias, 'vehicle', 'maintenanceVehicle');
        $this->join($queryBuilder, $rootAlias, 'maintenanceType', 'maintenanceType');
        $this->search($queryBuilder, $rootAlias, $params, ['notes'], [
            'maintenanceVehicle.registration',
            'maintenanceVehicle.name',
            'maintenanceType.name',
        ]);
        $this->equals($queryBuilder, $rootAlias, $params, 'status');
        $this->equals($queryBuilder, 'maintenanceVehicle', $params, 'vehicleId');
        $queryBuilder
            ->addSelect(sprintf(
                'COALESCE(%1$s.finishedAt, %1$s.startedAt, %1$s.plannedAt, %1$s.createdAt) AS HIDDEN eventDate',
                $rootAlias,
            ))
            ->orderBy('eventDate', 'DESC');
    }

    /** @param array<string, mixed> $params */
    private function inspections(QueryBuilder $queryBuilder, string $rootAlias, array $params): void
    {
        $this->join($queryBuilder, $rootAlias, 'vehicle', 'inspectionVehicle');
        $this->join($queryBuilder, $rootAlias, 'center', 'inspectionCenter');
        $this->search($queryBuilder, $rootAlias, $params, ['notes'], [
            'inspectionVehicle.registration',
            'inspectionVehicle.name',
            'inspectionCenter.name',
        ]);
        $this->equals($queryBuilder, 'inspectionVehicle', $params, 'vehicleId');
        $queryBuilder->orderBy($rootAlias.'.inspectionDate', 'DESC');
    }

    /** @param array<string, mixed> $params */
    private function insurances(QueryBuilder $queryBuilder, string $rootAlias, array $params): void
    {
        $this->join($queryBuilder, $rootAlias, 'vehicle', 'insuranceVehicle');
        $this->search($queryBuilder, $rootAlias, $params, [
            'providerName',
            'policyNumber',
        ], ['insuranceVehicle.registration', 'insuranceVehicle.name']);
        $this->equals($queryBuilder, 'insuranceVehicle', $params, 'vehicleId');
        $queryBuilder->orderBy($rootAlias.'.startDate', 'DESC');
    }

    private function join(QueryBuilder $queryBuilder, string $rootAlias, string $relation, string $alias): void
    {
        if (!in_array($alias, $queryBuilder->getAllAliases(), true)) {
            $queryBuilder->leftJoin($rootAlias.'.'.$relation, $alias);
        }
    }

    /**
     * @param array<string, mixed> $params
     * @param list<string> $rootFields
     * @param list<string> $joinedFields
     */
    private function search(
        QueryBuilder $queryBuilder,
        string $rootAlias,
        array $params,
        array $rootFields,
        array $joinedFields = [],
    ): void {
        $search = $this->string($params, 'search');

        if ($search === null) {
            return;
        }

        $conditions = [];

        foreach ($rootFields as $field) {
            $conditions[] = sprintf('LOWER(%s.%s) LIKE :list_search', $rootAlias, $field);
        }

        foreach ($joinedFields as $field) {
            $conditions[] = sprintf('LOWER(%s) LIKE :list_search', $field);
        }

        $queryBuilder
            ->andWhere(implode(' OR ', $conditions))
            ->setParameter('list_search', '%'.mb_strtolower($search).'%');
    }

    /** @param array<string, mixed> $params */
    private function equals(QueryBuilder $queryBuilder, string $alias, array $params, string $field): void
    {
        $value = $this->string($params, $field);

        if ($value === null || $value === 'all') {
            return;
        }

        $parameter = 'list_'.$field;

        if ($field === 'vehicleId') {
            $field = 'id';
        }

        $queryBuilder
            ->andWhere(sprintf('%s.%s = :%s', $alias, $field, $parameter))
            ->setParameter($parameter, $value);
    }

    /** @param array<string, mixed> $params */
    private function stock(QueryBuilder $queryBuilder, string $rootAlias, array $params): void
    {
        match ($this->string($params, 'stock')) {
            'ok' => $queryBuilder->andWhere($rootAlias.'.quantity > 1'),
            'low' => $queryBuilder->andWhere($rootAlias.'.quantity = 1'),
            'out' => $queryBuilder->andWhere($rootAlias.'.quantity = 0'),
            default => null,
        };
    }

    /** @param array<string, mixed> $params */
    private function role(QueryBuilder $queryBuilder, string $rootAlias, array $params): void
    {
        match ($this->string($params, 'role')) {
            'admin' => $queryBuilder->andWhere($rootAlias.'.roles LIKE :list_role')->setParameter('list_role', '%ROLE_ADMIN%'),
            'user' => $queryBuilder->andWhere($rootAlias.'.roles NOT LIKE :list_role')->setParameter('list_role', '%ROLE_ADMIN%'),
            default => null,
        };
    }

    /** @param array<string, mixed> $params */
    private function vehicleEditability(QueryBuilder $queryBuilder, string $rootAlias, array $params): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        match ($this->string($params, 'editability')) {
            'editable' => $queryBuilder->andWhere($rootAlias.'.user = :list_current_user'),
            'readonly' => $queryBuilder->andWhere($rootAlias.'.user != :list_current_user'),
            default => null,
        };

        $queryBuilder->setParameter('list_current_user', $user);
    }

    /** @param array<string, mixed> $params */
    private function userEditability(QueryBuilder $queryBuilder, string $rootAlias, array $params): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $editability = $this->string($params, 'editability');

        if ($editability === null || $editability === 'all') {
            return;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            if ($editability === 'readonly') {
                $queryBuilder->andWhere('1 = 0');
            }

            return;
        }

        match ($editability) {
            'editable' => $queryBuilder->andWhere($rootAlias.'.id = :list_current_user_id'),
            'readonly' => $queryBuilder->andWhere($rootAlias.'.id != :list_current_user_id'),
            default => null,
        };

        $queryBuilder->setParameter('list_current_user_id', $user->getId());
    }

    /** @param array<string, mixed> $params */
    private function string(array $params, string $name): ?string
    {
        $value = $params[$name] ?? null;

        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
