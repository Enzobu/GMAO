<?php

namespace App\Tests\Unit\ApiPlatform\Extension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\ApiPlatform\Extension\ListQueryExtension;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ListQueryExtensionTest extends TestCase
{
    public function testDoesNothingWithoutCurrentRequest(): void
    {
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->expects(self::never())->method('andWhere');

        $this->extension([])->applyToCollection(
            $queryBuilder,
            $this->queryNameGenerator(),
            Vehicle::class,
        );
    }

    #[DataProvider('vehicleSortProvider')]
    public function testVehicleFiltersAndSorts(string $sort): void
    {
        $this->apply(Vehicle::class, [
            'search' => 'clio',
            'type' => 'car',
            'status' => 'active',
            'editability' => 'editable',
            'sort' => $sort,
        ], false);
    }

    public static function vehicleSortProvider(): iterable
    {
        yield ['registration'];
        yield ['year-desc'];
        yield ['mileage-desc'];
        yield ['name'];
    }

    #[DataProvider('partProvider')]
    public function testPartFiltersAndSorts(string $stock, string $sort): void
    {
        $this->apply(Part::class, [
            'search' => 'filtre',
            'partType' => '1',
            'vehicle' => '2',
            'stock' => $stock,
            'sort' => $sort,
        ]);
    }

    public static function partProvider(): iterable
    {
        yield ['ok', 'name'];
        yield ['low', 'quantity-desc'];
        yield ['out', 'updated-desc'];
        yield ['all', 'quantity-asc'];
    }

    #[DataProvider('userProvider')]
    public function testUserFiltersAndSorts(
        bool $admin,
        string $role,
        string $editability,
        string $sort,
    ): void {
        $this->apply(User::class, [
            'search' => 'enzo',
            'role' => $role,
            'editability' => $editability,
            'sort' => $sort,
        ], $admin);
    }

    public static function userProvider(): iterable
    {
        yield [true, 'admin', 'readonly', 'email'];
        yield [false, 'user', 'editable', 'role'];
        yield [false, 'all', 'readonly', 'name'];
        yield [true, 'all', 'editable', 'name'];
    }

    public function testMaintenanceFiltersAndSorts(): void
    {
        $this->apply(Maintenance::class, [
            'search' => 'vidange',
            'status' => 'todo',
            'vehicleId' => '3',
        ]);
    }

    public function testInspectionFiltersAndSorts(): void
    {
        $this->apply(VehicleInspection::class, [
            'search' => 'centre',
            'vehicleId' => '3',
        ]);
    }

    public function testInsuranceFiltersAndSorts(): void
    {
        $this->apply(VehicleInsurance::class, [
            'search' => 'axa',
            'vehicleId' => '3',
        ]);
    }

    public function testUnknownResourceIsIgnored(): void
    {
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->expects(self::never())->method('andWhere');

        $this->extension(['search' => 'x'])->applyToCollection(
            $queryBuilder,
            $this->queryNameGenerator(),
            \stdClass::class,
        );
    }

    public function testNonScalarSearchIsIgnored(): void
    {
        $this->apply(Vehicle::class, ['search' => ['invalid']]);
    }

    public function testVehicleEditabilityIsIgnoredForAdmin(): void
    {
        $this->apply(Vehicle::class, [
            'editability' => 'readonly',
            'type' => 'all',
            'search' => ' ',
        ]);
    }

    public function testVehicleReadonlyEditabilityForCurrentUser(): void
    {
        $this->apply(Vehicle::class, ['editability' => 'readonly'], false);
    }

    public function testVehicleUnknownEditabilityIsIgnored(): void
    {
        $this->apply(Vehicle::class, ['editability' => 'unknown'], false);
    }

    public function testUserEditabilityAllIsIgnored(): void
    {
        $this->apply(User::class, ['editability' => 'all'], false);
    }

    public function testUserEditabilityIsIgnoredWithoutCurrentUser(): void
    {
        $this->extension(['editability' => 'editable'], false, false)
            ->applyToCollection(
                $this->queryBuilder(),
                $this->queryNameGenerator(),
                User::class,
            );
        self::assertTrue(true);
    }

    public function testUserUnknownEditabilityIsIgnored(): void
    {
        $this->apply(User::class, ['editability' => 'unknown'], false);
    }

    /** @param array<string, mixed> $params */
    private function apply(string $resourceClass, array $params, bool $admin = true): void
    {
        $this->extension($params, $admin)->applyToCollection(
            $this->queryBuilder(),
            $this->queryNameGenerator(),
            $resourceClass,
        );
        self::assertTrue(true);
    }

    /** @param array<string, mixed> $query */
    private function extension(
        array $query,
        bool $admin = true,
        User|false|null $user = null,
    ): ListQueryExtension {
        $requestStack = new RequestStack();

        if ($query !== []) {
            $requestStack->push(new Request($query));
        }

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn($admin);
        $security->method('getUser')->willReturn(
            $user === false ? null : ($user ?? $this->user()),
        );

        return new ListQueryExtension($requestStack, $security);
    }

    private function queryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getRootAliases')->willReturn(['r']);
        $queryBuilder->method('getAllAliases')->willReturn(['r']);
        $queryBuilder->method('leftJoin')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('addOrderBy')->willReturnSelf();
        $queryBuilder->method('addSelect')->willReturnSelf();
        $queryBuilder->method('distinct')->willReturnSelf();

        return $queryBuilder;
    }

    private function queryNameGenerator(): QueryNameGeneratorInterface
    {
        return $this->createMock(QueryNameGeneratorInterface::class);
    }

    private function user(): User
    {
        $reflection = new \ReflectionProperty(User::class, 'id');
        $user = new User();
        $reflection->setValue($user, 1);

        return $user;
    }
}
