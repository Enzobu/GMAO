<?php

namespace App\Tests\Unit\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\State\VehicleInsuranceStateProcessor;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class VehicleInsuranceStateProcessorTest extends TestCase
{
    public function testReturnsNullForUnsupportedData(): void
    {
        $processor = new VehicleInsuranceStateProcessor($this->createMock(EntityManagerInterface::class), $this->createMock(Security::class));

        self::assertNull($processor->process(new \stdClass(), new Post()));
    }

    public function testOwnerCanPersistInsurance(): void
    {
        $user = new User();
        $insurance = (new VehicleInsurance())->setVehicle((new Vehicle())->setUser($user));
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($insurance);
        $em->expects(self::once())->method('flush');

        $result = (new VehicleInsuranceStateProcessor($em, $security))->process($insurance, new Post());

        self::assertSame($insurance, $result);
    }

    public function testAdminCanPersistInsuranceForAnyVehicle(): void
    {
        $insurance = (new VehicleInsurance())->setVehicle((new Vehicle())->setUser(new User()));
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new User());
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($insurance);
        $em->expects(self::once())->method('flush');

        $result = (new VehicleInsuranceStateProcessor($em, $security))->process($insurance, new Post());

        self::assertSame($insurance, $result);
    }


    public function testNonOwnerCannotPersistInsurance(): void
    {
        $insurance = (new VehicleInsurance())->setVehicle((new Vehicle())->setUser(new User()));
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new User());
        $security->method('isGranted')->with('ROLE_ADMIN')->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $this->expectException(AccessDeniedHttpException::class);

        (new VehicleInsuranceStateProcessor($em, $security))->process($insurance, new Post());
    }

    public function testDeleteSoftDeletesInsurance(): void
    {
        $insurance = new VehicleInsurance();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $result = (new VehicleInsuranceStateProcessor($em, $this->createMock(Security::class)))->process($insurance, new Delete());

        self::assertNull($result);
        self::assertTrue($insurance->isDeleted());
    }
}
