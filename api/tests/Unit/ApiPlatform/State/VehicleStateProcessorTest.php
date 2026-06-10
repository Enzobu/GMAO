<?php

namespace App\Tests\Unit\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\State\VehicleStateProcessor;
use App\Entity\User;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class VehicleStateProcessorTest extends TestCase
{
    public function testReturnsNullForUnsupportedData(): void
    {
        $processor = new VehicleStateProcessor($this->createMock(EntityManagerInterface::class), $this->createMock(Security::class));

        self::assertNull($processor->process(new \stdClass(), new Post()));
    }

    public function testCreateAssignsCurrentUserForNonAdmin(): void
    {
        $user = new User();
        $vehicle = new Vehicle();
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($vehicle);
        $em->expects(self::once())->method('flush');

        $result = (new VehicleStateProcessor($em, $security))->process($vehicle, new Post());

        self::assertSame($vehicle, $result);
        self::assertSame($user, $vehicle->getUser());
    }

    public function testAdminCreateKeepsExplicitOwner(): void
    {
        $admin = new User();
        $owner = new User();
        $vehicle = (new Vehicle())->setUser($owner);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($admin);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($vehicle);
        $em->expects(self::once())->method('flush');

        (new VehicleStateProcessor($em, $security))->process($vehicle, new Post());

        self::assertSame($owner, $vehicle->getUser());
    }

    public function testAdminCreateAssignsCurrentUserWhenOwnerMissing(): void
    {
        $admin = new User();
        $vehicle = new Vehicle();
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($admin);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($vehicle);
        $em->expects(self::once())->method('flush');

        (new VehicleStateProcessor($em, $security))->process($vehicle, new Post());

        self::assertSame($admin, $vehicle->getUser());
    }

    public function testNonAdminUpdateKeepsExistingOwner(): void
    {
        $user = new User();
        $owner = new User();
        $vehicle = (new Vehicle())->setUser($owner);
        $this->setId($vehicle, 7);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');
        $em->expects(self::once())->method('flush');

        $result = (new VehicleStateProcessor($em, $security))->process($vehicle, new Patch());

        self::assertSame($vehicle, $result);
        self::assertSame($owner, $vehicle->getUser());
    }

    public function testAdminUpdateKeepsExistingOwner(): void
    {
        $owner = new User();
        $vehicle = (new Vehicle())->setUser($owner);
        $this->setId($vehicle, 7);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new User());
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');
        $em->expects(self::once())->method('flush');

        $result = (new VehicleStateProcessor($em, $security))->process($vehicle, new Patch());

        self::assertSame($vehicle, $result);
        self::assertSame($owner, $vehicle->getUser());
    }

    public function testDeleteSoftDeletesVehicle(): void
    {
        $vehicle = new Vehicle();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $result = (new VehicleStateProcessor($em, $this->createMock(Security::class)))->process($vehicle, new Delete());

        self::assertNull($result);
        self::assertTrue($vehicle->isDeleted());
    }

    private function setId(Vehicle $vehicle, int $id): void
    {
        $property = new \ReflectionProperty($vehicle, 'id');
        $property->setValue($vehicle, $id);
    }
}
