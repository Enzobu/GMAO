<?php

namespace App\Tests\Unit\Controller\Api;

use App\Controller\Api\DashboardController;
use App\Entity\Maintenance;
use App\Entity\MaintenanceType;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Tests\Unit\Controller\ControllerTestContainer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class DashboardControllerTest extends TestCase
{
    public function testReturnsUnauthenticatedWhenNoTokenExists(): void
    {
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn(null);
        $controller = new DashboardController($this->createMock(EntityManagerInterface::class));
        $controller->setContainer(new ControllerTestContainer(['security.token_storage' => $storage]));

        $response = $controller();

        self::assertSame(401, $response->getStatusCode());
        self::assertSame(['message' => 'Unauthenticated'], json_decode($response->getContent(), true));
    }

    public function testReturnsDashboardForUser(): void
    {
        $today = new \DateTimeImmutable('today');
        $vehicle = (new Vehicle())->setName('clio')->setRegistration('ab-123-cd');
        $maintenanceType = (new MaintenanceType())->setName('Vidange');

        $plannedToday = (new Maintenance())
            ->setVehicle($vehicle)
            ->setMaintenanceType($maintenanceType)
            ->setPlannedAt($today);
        $plannedWithoutRelations = (new Maintenance())->setPlannedAt(null);
        $finishedWithoutDate = (new Maintenance())
            ->setVehicle($vehicle)
            ->setMaintenanceType($maintenanceType)
            ->setFinishedAt(null);
        $insurance = (new VehicleInsurance())
            ->setVehicle($vehicle)
            ->setProviderName('MAIF')
            ->setEndDate($today->modify('-2 days'))
            ->setCreatedAt($today->modify('-3 days'));
        $inspection = (new VehicleInspection())
            ->setVehicle($vehicle)
            ->setValidUntil($today->modify('+5 days'))
            ->setInspectionDate($today->modify('-1 day'));

        $entityManager = $this->entityManagerForResults([
            ['result' => [$plannedToday, $plannedWithoutRelations]],
            ['result' => [$insurance]],
            ['result' => [$inspection]],
            ['result' => [$finishedWithoutDate]],
            ['result' => [$insurance]],
            ['result' => [$inspection]],
            ['scalar' => '4'],
            ['scalar' => '9'],
            ['result' => [$vehicle, new Vehicle()]],
            ['column' => [1]],
            ['result' => [
                ['finishedAt' => $today->modify('-1 month')],
                ['finishedAt' => null],
                ['finishedAt' => 'ignored'],
            ]],
        ]);

        $response = $this->controller(new User(), $entityManager)();
        $payload = json_decode($response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(4, $payload['stats']['vehicles']);
        self::assertSame(9, $payload['stats']['maintenances']);
        self::assertSame(['percentage' => 50, 'upToDateVehicles' => 1, 'totalVehicles' => 2], $payload['stats']['maintenanceHealth']);
        self::assertSame(4, $payload['stats']['alerts']);
        self::assertCount(12, $payload['maintenanceHistory']);
        self::assertContains(1, array_column($payload['maintenanceHistory'], 'count'));

        self::assertSame('maintenance', $payload['upcoming'][0]['type']);
        self::assertSame('Entretien prévu', $payload['upcoming'][0]['title']);
        self::assertSame('Véhicule inconnu', $payload['upcoming'][0]['subtitle']);
        self::assertSame('', $payload['upcoming'][0]['date']);
        self::assertSame('insurance', $payload['upcoming'][1]['type']);
        self::assertSame('danger', $payload['upcoming'][1]['severity']);
        self::assertSame('Expirée depuis 2 j', $payload['upcoming'][1]['meta']);
        self::assertSame('maintenance', $payload['upcoming'][2]['type']);
        self::assertSame('Prévu aujourd\'hui', $payload['upcoming'][2]['meta']);
        self::assertSame('Expire dans 5 j', $payload['upcoming'][3]['meta']);

        self::assertSame('inspection', $payload['recentActivity'][0]['type']);
        self::assertSame('insurance', $payload['recentActivity'][1]['type']);
        self::assertSame('maintenance', $payload['recentActivity'][2]['type']);
        self::assertSame('', $payload['recentActivity'][2]['date']);
        self::assertSame('', $payload['recentActivity'][2]['meta']);
    }

    public function testReturnsDashboardForAdminWithoutUserRestrictionsOrVehicles(): void
    {
        $entityManager = $this->entityManagerForResults([
            ['result' => []],
            ['result' => []],
            ['result' => []],
            ['result' => []],
            ['result' => []],
            ['result' => []],
            ['scalar' => '0'],
            ['scalar' => '0'],
            ['result' => []],
            ['result' => []],
        ]);

        $response = $this->controller((new User())->setRoles(['ROLE_ADMIN']), $entityManager, true)();
        $payload = json_decode($response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['percentage' => 100, 'upToDateVehicles' => 0, 'totalVehicles' => 0], $payload['stats']['maintenanceHealth']);
        self::assertSame(0, $payload['stats']['alerts']);
        self::assertSame([], $payload['upcoming']);
        self::assertSame([], $payload['recentActivity']);
    }

    /**
     * @param list<array{scalar?:string,result?:array<int, mixed>,column?:array<int, mixed>}> $results
     */
    private function entityManagerForResults(array $results): EntityManagerInterface
    {
        $queryBuilders = array_map(
            fn (array $result): QueryBuilder => $this->queryBuilderForResult($result),
            $results,
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(count($queryBuilders)))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(...$queryBuilders);

        return $entityManager;
    }

    /**
     * @param array{scalar?:string,result?:array<int, mixed>,column?:array<int, mixed>} $result
     */
    private function queryBuilderForResult(array $result): QueryBuilder
    {
        $query = new class($result) extends Query {
            /**
             * @param array{scalar?:string,result?:array<int, mixed>,column?:array<int, mixed>} $result
             */
            public function __construct(private readonly array $result) {}

            public function getSingleScalarResult(): mixed
            {
                return $this->result['scalar'] ?? null;
            }

            public function getSingleColumnResult(): array
            {
                return $this->result['column'] ?? [];
            }

            public function getResult(string|int $hydrationMode = self::HYDRATE_OBJECT): mixed
            {
                return $this->result['result'] ?? [];
            }
        };

        $queryBuilder = $this->createMock(QueryBuilder::class);
        foreach (['select', 'from', 'join', 'andWhere', 'setParameter'] as $method) {
            $queryBuilder->method($method)->willReturnSelf();
        }
        $queryBuilder->method('getQuery')->willReturn($query);

        return $queryBuilder;
    }

    private function controller(User $user, EntityManagerInterface $entityManager, bool $isAdmin = false): DashboardController
    {
        $controller = new DashboardController($entityManager);
        $controller->setContainer(new ControllerTestContainer([
            'security.token_storage' => $this->tokenStorage($user),
            'security.authorization_checker' => $this->authorizationChecker($isAdmin),
        ]));

        return $controller;
    }

    private function tokenStorage(User $user): TokenStorageInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        return $storage;
    }

    private function authorizationChecker(bool $isAdmin): AuthorizationCheckerInterface
    {
        $checker = $this->createMock(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->with('ROLE_ADMIN')->willReturn($isAdmin);

        return $checker;
    }
}
