<?php

namespace App\Tests\Unit\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\State\PartStateProcessor;
use App\Entity\Part;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PartStateProcessorTest extends TestCase
{
    public function testReturnsNullForUnsupportedData(): void
    {
        $processor = new PartStateProcessor($this->createMock(EntityManagerInterface::class));

        self::assertNull($processor->process(new \stdClass(), new Post()));
    }

    public function testPersistsNewPartAndFlushes(): void
    {
        $part = new Part();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($part);
        $em->expects(self::once())->method('flush');

        $result = (new PartStateProcessor($em))->process($part, new Post());

        self::assertSame($part, $result);
    }

    public function testDeleteSoftDeletesPartAndFlushes(): void
    {
        $part = new Part();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');
        $em->expects(self::once())->method('flush');

        $result = (new PartStateProcessor($em))->process($part, new Delete());

        self::assertNull($result);
        self::assertTrue($part->isDeleted());
    }
}
