<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use PHPUnit\Framework\TestCase;

final class DocumentTest extends TestCase
{
    public function testAccessorsAndTimestamps(): void
    {
        $createdAt = new \DateTimeImmutable('2025-01-01');
        $updatedAt = new \DateTimeImmutable('2025-01-02');
        $document = (new Document())
            ->setName('Carte grise')
            ->setOriginalFilename('original.pdf')
            ->setStoredFilename('stored.pdf')
            ->setDeletedStoredFilename('deleted/stored.pdf')
            ->setMimeType('application/pdf')
            ->setSize(123)
            ->setExtension('pdf')
            ->setDescription('description')
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($updatedAt)
            ->setIsDeleted(true)
            ->setPublicId('public-id');

        self::assertSame('Carte grise', $document->getName());
        self::assertSame('original.pdf', $document->getOriginalFilename());
        self::assertSame('stored.pdf', $document->getStoredFilename());
        self::assertSame('deleted/stored.pdf', $document->getDeletedStoredFilename());
        self::assertSame('application/pdf', $document->getMimeType());
        self::assertSame(123, $document->getSize());
        self::assertSame('pdf', $document->getExtension());
        self::assertSame('description', $document->getDescription());
        self::assertSame($createdAt, $document->getCreatedAt());
        self::assertSame($updatedAt, $document->getUpdatedAt());
        self::assertTrue($document->isDeleted());
        self::assertSame('public-id', $document->getPublicId());
    }

    public function testGetParentReturnsFirstDefinedParent(): void
    {
        $parents = [new Vehicle(), new VehicleInsurance(), new VehicleInspection(), new User(), new Part(), new Maintenance()];
        $setters = ['setVehicle', 'setVehicleInsurance', 'setVehicleInspection', 'setUser', 'setPart', 'setMaintenance'];

        foreach ($parents as $index => $parent) {
            $document = new Document();
            $document->{$setters[$index]}($parent);

            self::assertSame($parent, $document->getParent());
        }
    }

    public function testUpdateTimestampRefreshesUpdatedAt(): void
    {
        $document = (new Document())->setUpdatedAt(new \DateTimeImmutable('2000-01-01'));

        $document->updateTimestamp();

        self::assertGreaterThan(new \DateTimeImmutable('2000-01-01'), $document->getUpdatedAt());
    }
}
