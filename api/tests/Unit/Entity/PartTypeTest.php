<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Part;
use App\Entity\PartType;
use PHPUnit\Framework\TestCase;

final class PartTypeTest extends TestCase
{
    public function testAccessorsAndSoftDeleteFlag(): void
    {
        $type = (new PartType())
            ->setName('Freinage')
            ->setDescription('Pieces de freinage')
            ->setIsDeleted(true);

        self::assertSame('Freinage', $type->getName());
        self::assertSame('Pieces de freinage', $type->getDescription());
        self::assertTrue($type->isDeleted());
    }

    public function testPartRelationIsBidirectional(): void
    {
        $type = new PartType();
        $part = new Part();

        $type->addPart($part);

        self::assertTrue($type->getParts()->contains($part));
        self::assertSame($type, $part->getPartType());

        $type->removePart($part);

        self::assertFalse($type->getParts()->contains($part));
        self::assertNull($part->getPartType());
    }
}
