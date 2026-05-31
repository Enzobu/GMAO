<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DocumentTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $document = new Document();

        $this::assertNull($document->getId());
        $this::assertSame('', $document->getName());
        $this::assertNull($document->getOriginalFilename());
        $this::assertNull($document->getStoredFilename());
        $this::assertNull($document->getDeletedStoredFilename());
        $this::assertNull($document->getMimeType());
        $this::assertNull($document->getSize());
        $this::assertNull($document->getExtension());
        $this::assertNull($document->getDescription());
        $this::assertFalse($document->isDeleted());
        $this::assertNull($document->getVehicle());
        $this::assertNull($document->getVehicleInsurance());
        $this::assertNull($document->getVehicleInspection());
        $this::assertNull($document->getUser());
        $this::assertNull($document->getPart());
        $this::assertNull($document->getMaintenance());
        $this::assertNull($document->getParent());
        $this::assertInstanceOf(\DateTimeImmutable::class, $document->getCreatedAt());
        $this::assertInstanceOf(\DateTimeImmutable::class, $document->getUpdatedAt());
        $this::assertNotNull($document->getPublicId());
        $this::assertTrue(Uuid::isValid($document->getPublicId()));
    }

    public function testAccessorsAndTimestamps(): void
    {
        $createdAt = new \DateTimeImmutable('2025-01-01');
        $updatedAt = new \DateTimeImmutable('2025-01-02');
        $document = new Document();

        $this::assertSame($document, $document->setName('Carte grise'));
        $this::assertSame($document, $document->setOriginalFilename('original.pdf'));
        $this::assertSame($document, $document->setStoredFilename('stored.pdf'));
        $this::assertSame($document, $document->setDeletedStoredFilename('deleted/stored.pdf'));
        $this::assertSame($document, $document->setMimeType('application/pdf'));
        $this::assertSame($document, $document->setSize(123));
        $this::assertSame($document, $document->setExtension('pdf'));
        $this::assertSame($document, $document->setDescription('description'));
        $this::assertSame($document, $document->setCreatedAt($createdAt));
        $this::assertSame($document, $document->setUpdatedAt($updatedAt));
        $this::assertSame($document, $document->setIsDeleted(true));
        $this::assertSame($document, $document->setPublicId('public-id'));

        $this::assertSame('Carte grise', $document->getName());
        $this::assertSame('original.pdf', $document->getOriginalFilename());
        $this::assertSame('stored.pdf', $document->getStoredFilename());
        $this::assertSame('deleted/stored.pdf', $document->getDeletedStoredFilename());
        $this::assertSame('application/pdf', $document->getMimeType());
        $this::assertSame(123, $document->getSize());
        $this::assertSame('pdf', $document->getExtension());
        $this::assertSame('description', $document->getDescription());
        $this::assertSame($createdAt, $document->getCreatedAt());
        $this::assertSame($updatedAt, $document->getUpdatedAt());
        $this::assertTrue($document->isDeleted());
        $this::assertSame('public-id', $document->getPublicId());
    }

    public function testNullableAccessorsCanBeReset(): void
    {
        $document = (new Document())
            ->setOriginalFilename('original.pdf')
            ->setStoredFilename('stored.pdf')
            ->setDeletedStoredFilename('deleted/stored.pdf')
            ->setMimeType('application/pdf')
            ->setSize(123)
            ->setExtension('pdf')
            ->setDescription('description')
            ->setPublicId('public-id');

        $document
            ->setOriginalFilename(null)
            ->setStoredFilename(null)
            ->setDeletedStoredFilename(null)
            ->setMimeType(null)
            ->setSize(null)
            ->setExtension(null)
            ->setDescription(null)
            ->setPublicId(null);

        $this::assertNull($document->getOriginalFilename());
        $this::assertNull($document->getStoredFilename());
        $this::assertNull($document->getDeletedStoredFilename());
        $this::assertNull($document->getMimeType());
        $this::assertNull($document->getSize());
        $this::assertNull($document->getExtension());
        $this::assertNull($document->getDescription());
        $this::assertNull($document->getPublicId());
    }

    public function testParentAccessors(): void
    {
        $vehicle = new Vehicle();
        $vehicleInsurance = new VehicleInsurance();
        $vehicleInspection = new VehicleInspection();
        $user = new User();
        $part = new Part();
        $maintenance = new Maintenance();

        $document = new Document();

        $this::assertSame($document, $document->setVehicle($vehicle));
        $this::assertSame($document, $document->setVehicleInsurance($vehicleInsurance));
        $this::assertSame($document, $document->setVehicleInspection($vehicleInspection));
        $this::assertSame($document, $document->setUser($user));
        $this::assertSame($document, $document->setPart($part));
        $this::assertSame($document, $document->setMaintenance($maintenance));

        $this::assertSame($vehicle, $document->getVehicle());
        $this::assertSame($vehicleInsurance, $document->getVehicleInsurance());
        $this::assertSame($vehicleInspection, $document->getVehicleInspection());
        $this::assertSame($user, $document->getUser());
        $this::assertSame($part, $document->getPart());
        $this::assertSame($maintenance, $document->getMaintenance());
    }

    #[DataProvider('parentProvider')]
    public function testGetParentReturnsDefinedParent(string $setter, object $parent): void
    {
        $document = new Document();
        $document->{$setter}($parent);

        $this::assertSame($parent, $document->getParent());
    }

    /**
     * @return iterable<string, array{string, object}>
     */
    public static function parentProvider(): iterable
    {
        yield 'vehicle' => ['setVehicle', new Vehicle()];
        yield 'vehicle insurance' => ['setVehicleInsurance', new VehicleInsurance()];
        yield 'vehicle inspection' => ['setVehicleInspection', new VehicleInspection()];
        yield 'user' => ['setUser', new User()];
        yield 'part' => ['setPart', new Part()];
        yield 'maintenance' => ['setMaintenance', new Maintenance()];
    }

    public function testGetParentReturnsFirstParentByPriority(): void
    {
        $vehicle = new Vehicle();

        $document = (new Document())
            ->setVehicle($vehicle)
            ->setVehicleInsurance(new VehicleInsurance())
            ->setVehicleInspection(new VehicleInspection())
            ->setUser(new User())
            ->setPart(new Part())
            ->setMaintenance(new Maintenance());

        $this::assertSame($vehicle, $document->getParent());
    }

    public function testParentAccessorsCanBeReset(): void
    {
        $document = (new Document())
            ->setVehicle(new Vehicle())
            ->setVehicleInsurance(new VehicleInsurance())
            ->setVehicleInspection(new VehicleInspection())
            ->setUser(new User())
            ->setPart(new Part())
            ->setMaintenance(new Maintenance());

        $document
            ->setVehicle(null)
            ->setVehicleInsurance(null)
            ->setVehicleInspection(null)
            ->setUser(null)
            ->setPart(null)
            ->setMaintenance(null);

        $this::assertNull($document->getVehicle());
        $this::assertNull($document->getVehicleInsurance());
        $this::assertNull($document->getVehicleInspection());
        $this::assertNull($document->getUser());
        $this::assertNull($document->getPart());
        $this::assertNull($document->getMaintenance());
        $this::assertNull($document->getParent());
    }

    public function testUpdateTimestampRefreshesUpdatedAt(): void
    {
        $document = (new Document())->setUpdatedAt(new \DateTimeImmutable('2000-01-01'));

        $document->updateTimestamp();

        $this::assertGreaterThan(new \DateTimeImmutable('2000-01-01'), $document->getUpdatedAt());
    }
}
