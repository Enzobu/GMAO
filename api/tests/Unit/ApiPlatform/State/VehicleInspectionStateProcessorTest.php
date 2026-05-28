<?php

namespace App\Tests\Unit\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\State\VehicleInspectionStateProcessor;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class VehicleInspectionStateProcessorTest extends TestCase
{
    public function testReturnsNullForUnsupportedData(): void
    {
        $processor = new VehicleInspectionStateProcessor($this->createMock(EntityManagerInterface::class), $this->createMock(Security::class));

        self::assertNull($processor->process(new \stdClass(), new Post()));
    }

    public function testOwnerCanPersistInspection(): void
    {
        $user = new User();
        $inspection = (new VehicleInspection())->setVehicle((new Vehicle())->setUser($user));
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($inspection);
        $em->expects(self::once())->method('flush');

        $result = (new VehicleInspectionStateProcessor($em, $security))->process($inspection, new Post());

        self::assertSame($inspection, $result);
    }

    public function testNonOwnerCannotPersistInspection(): void
    {
        $inspection = (new VehicleInspection())->setVehicle((new Vehicle())->setUser(new User()));
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new User());
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $this->expectException(AccessDeniedHttpException::class);

        (new VehicleInspectionStateProcessor($em, $security))->process($inspection, new Post());
    }

    public function testDeleteSoftDeletesInspection(): void
    {
        $inspection = new VehicleInspection();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $result = (new VehicleInspectionStateProcessor($em, $this->createMock(Security::class)))->process($inspection, new Delete());

        self::assertNull($result);
        self::assertTrue($inspection->isDeleted());
    }
}
