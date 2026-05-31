<?php

namespace App\Tests\Unit\Service;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Repository\DocumentRepository;
use App\Service\DocumentParentResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DocumentParentResolverTest extends TestCase
{
    #[DataProvider('parentProvider')]
    public function testResolveFindsSupportedParent(string $type, object $parent, string $class): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('find')->with($class, 12)->willReturn($parent);

        self::assertSame($parent, $this->resolver($entityManager)->resolve($type, 12));
    }

    public function testResolveRejectsUnsupportedParent(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('find');

        self::assertNull($this->resolver($entityManager)->resolve('unknown', 12));
    }

    public function testResolveRejectsUnexpectedEntity(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('find')->with(User::class, 12)->willReturn(new \stdClass());

        self::assertNull($this->resolver($entityManager)->resolve('users', 12));
    }

    #[DataProvider('documentProvider')]
    public function testDocumentsDelegatesToRepository(object $parent, string $method): void
    {
        $documents = [new Document()];
        $repository = $this->createMock(DocumentRepository::class);
        $repository->expects(self::once())->method($method)->with($parent)->willReturn($documents);

        self::assertSame($documents, $this->resolver(repository: $repository)->documents($parent));
    }

    #[DataProvider('belongingProvider')]
    public function testBelongsToParent(Document $document, object $parent): void
    {
        self::assertTrue($this->resolver()->belongsToParent($document, $parent));
    }

    public function testBelongsToParentRejectsDifferentParent(): void
    {
        $document = (new Document())->setUser($this->withId(new User(), 1));

        self::assertFalse($this->resolver()->belongsToParent($document, $this->withId(new User(), 2)));
    }

    public function testBelongsToParentRejectsMissingParent(): void
    {
        self::assertFalse($this->resolver()->belongsToParent(new Document(), new User()));
    }

    #[DataProvider('attachProvider')]
    public function testAttachLinksDocumentToParent(object $parent, string $getter): void
    {
        $document = new Document();

        $this->resolver()->attach($document, $parent);

        self::assertSame($parent, $document->{$getter}());
    }

    public function testOwningVehicleResolvesVehicleParents(): void
    {
        $vehicle = new Vehicle();

        self::assertSame($vehicle, $this->resolver()->owningVehicle($vehicle));
        self::assertSame($vehicle, $this->resolver()->owningVehicle((new VehicleInsurance())->setVehicle($vehicle)));
        self::assertSame($vehicle, $this->resolver()->owningVehicle((new VehicleInspection())->setVehicle($vehicle)));
        self::assertSame($vehicle, $this->resolver()->owningVehicle((new Maintenance())->setVehicle($vehicle)));
        self::assertNull($this->resolver()->owningVehicle(new User()));
    }

    #[DataProvider('deletedParentProvider')]
    public function testIsDeletedReadsParentFlag(object $parent, bool $expected): void
    {
        self::assertSame($expected, $this->resolver()->isDeleted($parent));
    }

    public static function parentProvider(): iterable
    {
        yield 'user' => ['users', new User(), User::class];
        yield 'vehicle' => ['vehicles', new Vehicle(), Vehicle::class];
        yield 'insurance' => ['vehicle_insurances', new VehicleInsurance(), VehicleInsurance::class];
        yield 'inspection' => ['vehicle_inspections', new VehicleInspection(), VehicleInspection::class];
        yield 'maintenance' => ['maintenances', new Maintenance(), Maintenance::class];
        yield 'part' => ['parts', new Part(), Part::class];
    }

    public static function documentProvider(): iterable
    {
        yield 'user' => [new User(), 'findByUser'];
        yield 'vehicle' => [new Vehicle(), 'findByVehicle'];
        yield 'insurance' => [new VehicleInsurance(), 'findByVehicleInsurance'];
        yield 'inspection' => [new VehicleInspection(), 'findByVehicleInspection'];
        yield 'maintenance' => [new Maintenance(), 'findByMaintenance'];
        yield 'part' => [new Part(), 'findByPart'];
    }

    public static function belongingProvider(): iterable
    {
        $user = new User();
        yield 'user identity' => [(new Document())->setUser($user), $user];

        yield 'vehicle id' => [(new Document())->setVehicle(self::withStaticId(new Vehicle(), 1)), self::withStaticId(new Vehicle(), 1)];
        yield 'insurance id' => [(new Document())->setVehicleInsurance(self::withStaticId(new VehicleInsurance(), 1)), self::withStaticId(new VehicleInsurance(), 1)];
        yield 'inspection id' => [(new Document())->setVehicleInspection(self::withStaticId(new VehicleInspection(), 1)), self::withStaticId(new VehicleInspection(), 1)];
        yield 'maintenance id' => [(new Document())->setMaintenance(self::withStaticId(new Maintenance(), 1)), self::withStaticId(new Maintenance(), 1)];
        yield 'part id' => [(new Document())->setPart(self::withStaticId(new Part(), 1)), self::withStaticId(new Part(), 1)];
    }

    public static function attachProvider(): iterable
    {
        yield 'user' => [new User(), 'getUser'];
        yield 'vehicle' => [new Vehicle(), 'getVehicle'];
        yield 'insurance' => [new VehicleInsurance(), 'getVehicleInsurance'];
        yield 'inspection' => [new VehicleInspection(), 'getVehicleInspection'];
        yield 'maintenance' => [new Maintenance(), 'getMaintenance'];
        yield 'part' => [new Part(), 'getPart'];
    }

    public static function deletedParentProvider(): iterable
    {
        yield 'user' => [new User(), false];
        yield 'vehicle' => [(new Vehicle())->setIsDeleted(true), true];
        yield 'insurance' => [(new VehicleInsurance())->setIsDeleted(true), true];
        yield 'inspection' => [(new VehicleInspection())->setIsDeleted(true), true];
        yield 'maintenance' => [(new Maintenance())->setIsDeleted(true), true];
        yield 'part' => [(new Part())->setIsDeleted(true), true];
    }

    private function resolver(?EntityManagerInterface $entityManager = null, ?DocumentRepository $repository = null): DocumentParentResolver
    {
        return new DocumentParentResolver(
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $repository ?? $this->createMock(DocumentRepository::class),
        );
    }

    private function withId(object $entity, int $id): object
    {
        return self::withStaticId($entity, $id);
    }

    private static function withStaticId(object $entity, int $id): object
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);

        return $entity;
    }
}
