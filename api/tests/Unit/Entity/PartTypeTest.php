<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Part;
use App\Entity\PartType;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

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

    public function testNameMustBeUnique(): void
    {
        $uniqueEntityAttributes = (new \ReflectionClass(PartType::class))
            ->getAttributes(UniqueEntity::class);

        self::assertCount(1, $uniqueEntityAttributes);
        $attribute = $uniqueEntityAttributes[0]->newInstance();

        self::assertSame(['name'], $attribute->fields);
        self::assertSame('Ce type de pièce existe déjà.', $attribute->message);
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
