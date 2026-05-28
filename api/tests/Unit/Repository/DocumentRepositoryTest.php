<?php

namespace App\Tests\Unit\Repository;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Repository\DocumentRepository;
use PHPUnit\Framework\Attributes\DataProvider;

final class DocumentRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(DocumentRepository::class, $this->instantiateRepository(DocumentRepository::class, Document::class));
    }

    #[DataProvider('documentOwnerProvider')]
    public function testFindsDocumentsForOwner(string $method, object $owner, string $field, string $parameter): void
    {
        $documents = [new Document()];
        [$queryBuilder, , $calls] = $this->createRecordingQueryBuilder($documents);
        $repository = $this->instantiateRepositoryWithQueryBuilder(DocumentRepository::class, Document::class, $queryBuilder);

        self::assertSame($documents, $repository->{$method}($owner, true));
        $this->assertRecordedCall($calls, 'andWhere', ['d.' . $field . ' = :' . $parameter]);
        $this->assertRecordedCall($calls, 'setParameter', [$parameter, $owner]);
        $this->assertRecordedCall($calls, 'andWhere', ['d.isDeleted = :deleted']);
        $this->assertRecordedCall($calls, 'setParameter', ['deleted', true]);
        $this->assertRecordedCall($calls, 'orderBy', ['d.createdAt', 'DESC']);
    }

    public static function documentOwnerProvider(): iterable
    {
        yield 'user' => ['findByUser', new User(), 'user', 'user'];
        yield 'vehicle' => ['findByVehicle', new Vehicle(), 'vehicle', 'vehicle'];
        yield 'vehicle inspection' => ['findByVehicleInspection', new VehicleInspection(), 'vehicleInspection', 'vehicleInspection'];
        yield 'vehicle insurance' => ['findByVehicleInsurance', new VehicleInsurance(), 'vehicleInsurance', 'vehicleInsurance'];
        yield 'part' => ['findByPart', new Part(), 'part', 'part'];
        yield 'maintenance' => ['findByMaintenance', new Maintenance(), 'maintenance', 'maintenance'];
    }
}
