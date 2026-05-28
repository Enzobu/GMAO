<?php

namespace App\Tests\Unit\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\State\PartTypeStateProcessor;
use App\Entity\Part;
use App\Entity\PartType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PartTypeStateProcessorTest extends TestCase
{
    public function testReturnsNullForUnsupportedData(): void
    {
        $processor = new PartTypeStateProcessor($this->createMock(EntityManagerInterface::class));

        self::assertNull($processor->process(new \stdClass(), new Post()));
    }

    public function testPersistsNewPartTypeAndFlushes(): void
    {
        $type = new PartType();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($type);
        $em->expects(self::once())->method('flush');

        $result = (new PartTypeStateProcessor($em))->process($type, new Post());

        self::assertSame($type, $result);
    }

    public function testDeleteSoftDeletesPartTypeWhenUnused(): void
    {
        $type = new PartType();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $result = (new PartTypeStateProcessor($em))->process($type, new Delete());

        self::assertNull($result);
        self::assertTrue($type->isDeleted());
    }

    public function testDeleteThrowsConflictWhenPartsStillUseType(): void
    {
        $type = new PartType();
        $type->addPart(new Part());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $this->expectException(ConflictHttpException::class);

        (new PartTypeStateProcessor($em))->process($type, new Delete());
    }
}
