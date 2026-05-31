<?php

namespace App\Tests\Unit\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\State\MaintenanceTypeStateProcessor;
use App\Entity\MaintenanceType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class MaintenanceTypeStateProcessorTest extends TestCase
{
    public function testReturnsNullForUnsupportedData(): void
    {
        $processor = new MaintenanceTypeStateProcessor($this->createMock(EntityManagerInterface::class));

        self::assertNull($processor->process(new \stdClass(), new Post()));
    }

    public function testPersistsNewMaintenanceTypeAndFlushes(): void
    {
        $type = new MaintenanceType();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($type);
        $em->expects(self::once())->method('flush');

        $result = (new MaintenanceTypeStateProcessor($em))->process($type, new Post());

        self::assertSame($type, $result);
    }

    public function testDeleteSoftDeletesMaintenanceTypeAndFlushes(): void
    {
        $type = new MaintenanceType();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');
        $em->expects(self::once())->method('flush');

        $result = (new MaintenanceTypeStateProcessor($em))->process($type, new Delete());

        self::assertNull($result);
        self::assertTrue($type->isDeleted());
    }
}
