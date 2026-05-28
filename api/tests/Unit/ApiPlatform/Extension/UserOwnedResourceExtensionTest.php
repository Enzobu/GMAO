<?php

namespace App\Tests\Unit\ApiPlatform\Extension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\ApiPlatform\Extension\UserOwnedResourceExtension;
use App\Entity\Address;
use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\MaintenancePart;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class UserOwnedResourceExtensionTest extends TestCase
{
    public function testAnonymousUserGetsImpossiblePredicate(): void
    {
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->expects(self::exactly(2))
            ->method('andWhere')
            ->willReturnCallback(function (string $condition) use ($queryBuilder): QueryBuilder {
                self::assertContains($condition, ['d.isDeleted = :api_is_deleted', '1 = 0']);

                return $queryBuilder;
            });
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $security->method('getUser')->willReturn(null);

        $this->extension($security)->applyToCollection($queryBuilder, $this->queryNameGenerator(), Document::class);
    }

    public function testAdminOnlyGetsDeletedFilter(): void
    {
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->expects(self::once())->method('andWhere')->with('d.isDeleted = :api_is_deleted')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('setParameter')->with('api_is_deleted', false)->willReturnSelf();
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);

        $this->extension($security)->applyToItem($queryBuilder, $this->queryNameGenerator(), Document::class, []);
    }

    public function testDocumentRestrictionAddsOwnerJoins(): void
    {
        $user = new User();
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->expects(self::exactly(9))->method('leftJoin')->willReturnSelf();
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $security->method('getUser')->willReturn($user);

        $this->extension($security)->applyToCollection($queryBuilder, $this->queryNameGenerator(), Document::class);
    }

    public function testResourceWithoutDeletedFieldDoesNotAddDeletedFilter(): void
    {
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->expects(self::never())->method('andWhere');
        $queryBuilder->expects(self::never())->method('setParameter');
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $security->method('getUser')->willReturn(new User());

        $this->extension($security, false)->applyToCollection($queryBuilder, $this->queryNameGenerator(), Part::class);
    }

    public function testAddressRestrictionUsesCurrentUsersAddress(): void
    {
        $address = new Address();
        $user = (new User())->setAddress($address);
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->expects(self::exactly(2))
            ->method('setParameter')
            ->willReturnCallback(function (string $name, mixed $value) use ($queryBuilder, $address): QueryBuilder {
                if ($name === 'api_is_deleted') {
                    self::assertFalse($value);
                } else {
                    self::assertSame('api_current_user_address', $name);
                    self::assertSame($address, $value);
                }

                return $queryBuilder;
            });
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $security->method('getUser')->willReturn($user);

        $this->extension($security)->applyToCollection($queryBuilder, $this->queryNameGenerator(), Address::class);
    }

    public function testMaintenancePartRestrictionJoinsThroughMaintenanceVehicle(): void
    {
        $user = new User();
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->expects(self::exactly(2))->method('join')->willReturnSelf();
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $security->method('getUser')->willReturn($user);

        $this->extension($security)->applyToItem($queryBuilder, $this->queryNameGenerator(), MaintenancePart::class, []);
    }

    public function testVehicleInsuranceRestrictionJoinsThroughVehicle(): void
    {
        $user = new User();
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('join')->with('d.vehicle', 'vehicle_1')->willReturnSelf();
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $security->method('getUser')->willReturn($user);

        $this->extension($security)->applyToCollection($queryBuilder, $this->queryNameGenerator(), VehicleInsurance::class);
    }

    public function testVehicleResourceOnlyGetsDeletedFilterForCurrentUser(): void
    {
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->expects(self::once())->method('andWhere')->with('d.isDeleted = :api_is_deleted')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('setParameter')->with('api_is_deleted', false)->willReturnSelf();
        $queryBuilder->expects(self::never())->method('join');
        $queryBuilder->expects(self::never())->method('leftJoin');
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $security->method('getUser')->willReturn(new User());

        $this->extension($security)->applyToCollection($queryBuilder, $this->queryNameGenerator(), Vehicle::class);
    }

    public function testCurrentUserResourceOnlyGetsDeletedFilter(): void
    {
        $this->assertCurrentUserResourceOnlyGetsDeletedFilter(User::class);
    }

    public function testMaintenanceResourceOnlyGetsDeletedFilter(): void
    {
        $this->assertCurrentUserResourceOnlyGetsDeletedFilter(Maintenance::class);
    }

    public function testUnknownResourceOnlyGetsDeletedFilter(): void
    {
        $this->assertCurrentUserResourceOnlyGetsDeletedFilter(\stdClass::class);
    }

    private function assertCurrentUserResourceOnlyGetsDeletedFilter(string $resourceClass): void
    {
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->expects(self::once())->method('andWhere')->with('d.isDeleted = :api_is_deleted')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('setParameter')->with('api_is_deleted', false)->willReturnSelf();
        $queryBuilder->expects(self::never())->method('join');
        $queryBuilder->expects(self::never())->method('leftJoin');
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $security->method('getUser')->willReturn(new User());

        $this->extension($security)->applyToCollection($queryBuilder, $this->queryNameGenerator(), $resourceClass);
    }

    private function extension(Security $security, bool $hasDeletedField = true): UserOwnedResourceExtension
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasField')->with('isDeleted')->willReturn($hasDeletedField);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getClassMetadata')->willReturn($metadata);

        return new UserOwnedResourceExtension($security, $em);
    }

    private function queryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getRootAliases')->willReturn(['d']);

        return $queryBuilder;
    }

    private function queryNameGenerator(): QueryNameGeneratorInterface
    {
        $index = 0;
        $generator = $this->createMock(QueryNameGeneratorInterface::class);
        $generator->method('generateJoinAlias')->willReturnCallback(static function (string $association) use (&$index): string {
            return $association.'_'.++$index;
        });

        return $generator;
    }
}
