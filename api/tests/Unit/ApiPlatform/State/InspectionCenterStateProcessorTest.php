<?php

namespace App\Tests\Unit\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\State\InspectionCenterStateProcessor;
use App\Entity\InspectionCenter;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class InspectionCenterStateProcessorTest extends TestCase
{
    public function testReturnsNullForUnsupportedData(): void
    {
        $processor = new InspectionCenterStateProcessor($this->createMock(EntityManagerInterface::class));

        self::assertNull($processor->process(new \stdClass(), new Post()));
    }

    public function testPersistsNewInspectionCenterAndFlushes(): void
    {
        $center = new InspectionCenter();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($center);
        $em->expects(self::once())->method('flush');

        $result = (new InspectionCenterStateProcessor($em))->process($center, new Post());

        self::assertSame($center, $result);
    }

    public function testDeleteSoftDeletesInspectionCenterAndFlushes(): void
    {
        $center = new InspectionCenter();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');
        $em->expects(self::once())->method('flush');

        $result = (new InspectionCenterStateProcessor($em))->process($center, new Delete());

        self::assertNull($result);
        self::assertTrue($center->isDeleted());
    }
}
