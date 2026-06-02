<?php

namespace App\Tests\Unit\Service;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\MaintenanceType;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Enum\InspectionResultEnum;
use App\Enum\InsurancePaymentFrequencyEnum;
use App\Enum\MaintenanceStatusEnum;
use App\Repository\DocumentRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\VehicleInspectionRepository;
use App\Repository\VehicleInsuranceRepository;
use App\Service\DocumentManager;
use App\Service\VehicleHistoryArchiveBuilder;
use App\Service\VehicleHistoryArchiveFormatter;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class VehicleHistoryArchiveBuilderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/gmao-history-'.bin2hex(random_bytes(6));
        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->directory)) {
            $this->removeDirectory($this->directory);
        }
    }

    public function testBuildCreatesExpectedVehicleHistoryArchive(): void
    {
        $vehicle = $this->vehicle();
        $insurance = $this->insurance($vehicle);
        $inspection = $this->inspection($vehicle);
        $maintenance = $this->maintenance($vehicle);
        $vehicleDocument = $this->document('carte grise.pdf', 'vehicle.pdf');
        $insuranceDocument = $this->document('attestation.pdf', 'insurance.pdf');
        $inspectionDocument = $this->document('rapport.pdf', 'inspection.pdf');
        $maintenanceDocument = $this->document('facture.pdf', 'maintenance.pdf');
        $builder = $this->builder(
            [$insurance],
            [$inspection],
            [$maintenance],
            [
                'vehicle' => [$vehicleDocument],
                'insurance' => [$insuranceDocument],
                'inspection' => [$inspectionDocument],
                'maintenance' => [$maintenanceDocument],
            ],
        );

        $archive = $builder->build($vehicle);
        $entries = $this->zipEntries($archive->path);

        self::assertSame('historique_Clio_AA-123-AA.zip', $archive->filename);
        self::assertContains(
            'informations.md',
            $entries,
        );
        self::assertContains(
            'carte_grise.pdf',
            $entries,
        );
        self::assertContains(
            'assurances/assurance_Axa_POL123/informations.md',
            $entries,
        );
        self::assertContains(
            'assurances/assurance_Axa_POL123/attestation.pdf',
            $entries,
        );
        self::assertContains(
            'controles_technique/controle_technique_02-06-2026/informations.md',
            $entries,
        );
        self::assertContains(
            'controles_technique/controle_technique_02-06-2026/rapport.pdf',
            $entries,
        );
        self::assertContains(
            'interventions/intervention_02-06-2026_Vidange/informations.md',
            $entries,
        );
        self::assertContains(
            'interventions/intervention_02-06-2026_Vidange/facture.pdf',
            $entries,
        );
        $stats = $this->zipStats($archive->path);
        $insuranceMtime = (new \DateTimeImmutable('2026-01-01'))->getTimestamp();
        $eventMtime = (new \DateTimeImmutable('2026-06-02'))->getTimestamp();

        self::assertSame($insuranceMtime, $stats['assurances/assurance_Axa_POL123/']['mtime']);
        self::assertSame($insuranceMtime, $stats['assurances/assurance_Axa_POL123/attestation.pdf']['mtime']);
        self::assertSame($eventMtime, $stats['controles_technique/controle_technique_02-06-2026/']['mtime']);
        self::assertSame($eventMtime, $stats['controles_technique/controle_technique_02-06-2026/rapport.pdf']['mtime']);
        self::assertSame($eventMtime, $stats['interventions/intervention_02-06-2026_Vidange/']['mtime']);
        self::assertSame($eventMtime, $stats['interventions/intervention_02-06-2026_Vidange/facture.pdf']['mtime']);

        unlink($archive->path);
    }

    public function testBuildKeepsInformationFilesWithoutDocuments(): void
    {
        $vehicle = $this->vehicle();
        $insurance = $this->insurance($vehicle, null);
        $builder = $this->builder([$insurance], [], [], []);

        $archive = $builder->build($vehicle);
        $entries = $this->zipEntries($archive->path);

        self::assertContains(
            'assurances/assurance_Axa/informations.md',
            $entries,
        );

        unlink($archive->path);
    }

    public function testBuildKeepsUniqueNamesAndSkipsMissingFiles(): void
    {
        $vehicle = $this->vehicle();
        $firstInsurance = $this->insurance($vehicle, null);
        $secondInsurance = $this->insurance($vehicle, null);
        $firstDocument = $this->document('carte grise.pdf', 'first.pdf');
        $secondDocument = $this->document('carte grise.pdf', 'second.pdf');
        $imageDocument = $this->document('photo.jpg', 'photo.jpg');
        $missingDocument = (new Document())
            ->setName('missing.pdf')
            ->setOriginalFilename('missing.pdf')
            ->setStoredFilename('missing.pdf');
        $builder = $this->builder(
            [$firstInsurance, $secondInsurance],
            [],
            [],
            ['insurance' => [
                $firstDocument,
                $secondDocument,
                $imageDocument,
                $missingDocument,
            ]],
            ['missing.pdf' => false],
        );

        $archive = $builder->build($vehicle);
        $entries = $this->zipEntries($archive->path);

        self::assertContains(
            'assurances/assurance_Axa/informations.md',
            $entries,
        );
        self::assertContains(
            'assurances/assurance_Axa_2/informations.md',
            $entries,
        );
        self::assertContains(
            'assurances/assurance_Axa/carte_grise.pdf',
            $entries,
        );
        self::assertContains(
            'assurances/assurance_Axa/carte_grise_2.pdf',
            $entries,
        );
        self::assertContains(
            'assurances/assurance_Axa/photo.jpg',
            $entries,
        );
        self::assertNotContains(
            'assurances/assurance_Axa/missing.pdf',
            $entries,
        );

        unlink($archive->path);
    }

    /**
     * @param VehicleInsurance[] $insurances
     * @param VehicleInspection[] $inspections
     * @param Maintenance[] $maintenances
     * @param array{
     *     vehicle?: Document[],
     *     insurance?: Document[],
     *     inspection?: Document[],
     *     maintenance?: Document[]
     * } $documents
     */
    private function builder(
        array $insurances,
        array $inspections,
        array $maintenances,
        array $documents,
        array $fileExistence = [],
    ): VehicleHistoryArchiveBuilder {
        $insuranceRepository = $this->createMock(VehicleInsuranceRepository::class);
        $insuranceRepository->method('findByVehicle')->willReturn($insurances);

        $inspectionRepository = $this->createMock(VehicleInspectionRepository::class);
        $inspectionRepository->method('findByVehicle')->willReturn($inspections);

        $maintenanceRepository = $this->createMock(MaintenanceRepository::class);
        $maintenanceRepository->method('findForVehicle')->willReturn($maintenances);

        $documentRepository = $this->createMock(DocumentRepository::class);
        $documentRepository->method('findByVehicle')->willReturn($documents['vehicle'] ?? []);
        $documentRepository->method('findByVehicleInsurance')->willReturn($documents['insurance'] ?? []);
        $documentRepository->method('findByVehicleInspection')->willReturn($documents['inspection'] ?? []);
        $documentRepository->method('findByMaintenance')->willReturn($documents['maintenance'] ?? []);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('fileExists')->willReturnCallback(
            fn (Document $document): bool => $fileExistence[$document->getStoredFilename()] ?? true,
        );
        $documentManager->method('getAbsolutePath')->willReturnCallback(
            fn (Document $document): string => $this->directory.'/'.$document->getStoredFilename(),
        );

        return new VehicleHistoryArchiveBuilder(
            $insuranceRepository,
            $inspectionRepository,
            $maintenanceRepository,
            $documentRepository,
            $documentManager,
            new VehicleHistoryArchiveFormatter(),
        );
    }

    private function vehicle(): Vehicle
    {
        return (new Vehicle())
            ->setName('clio')
            ->setRegistration('AA-123-AA')
            ->setBrand('renault')
            ->setModel('clio');
    }

    private function insurance(Vehicle $vehicle, ?string $policy = 'POL123'): VehicleInsurance
    {
        return (new VehicleInsurance())
            ->setVehicle($vehicle)
            ->setProviderName('Axa')
            ->setPolicyNumber($policy)
            ->setStartDate(new \DateTimeImmutable('2026-01-01'))
            ->setEndDate(new \DateTimeImmutable('2027-01-01'))
            ->setPaymentFrequency(InsurancePaymentFrequencyEnum::Monthly);
    }

    private function inspection(Vehicle $vehicle): VehicleInspection
    {
        return (new VehicleInspection())
            ->setVehicle($vehicle)
            ->setInspectionDate(new \DateTimeImmutable('2026-06-02'))
            ->setValidUntil(new \DateTimeImmutable('2028-06-02'))
            ->setMileage(125000)
            ->setResult(InspectionResultEnum::Pass);
    }

    private function maintenance(Vehicle $vehicle): Maintenance
    {
        $type = (new MaintenanceType())->setName('Vidange');

        return (new Maintenance())
            ->setVehicle($vehicle)
            ->setMaintenanceType($type)
            ->setFinishedAt(new \DateTimeImmutable('2026-06-02'))
            ->setMileage(125000)
            ->setStatus(MaintenanceStatusEnum::Completed)
            ->setIsExternal(false);
    }

    private function document(string $originalName, string $storedName): Document
    {
        file_put_contents($this->directory.'/'.$storedName, 'content');

        return (new Document())
            ->setName($originalName)
            ->setOriginalFilename($originalName)
            ->setStoredFilename($storedName);
    }

    /** @return string[] */
    private function zipEntries(string $path): array
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path));
        $entries = [];

        for ($index = 0; $index < $zip->numFiles; ++$index) {
            $name = $zip->getNameIndex($index);
            self::assertIsString($name);
            $entries[] = $name;
        }

        $zip->close();

        return $entries;
    }

    /** @return array<string, array<string, mixed>> */
    private function zipStats(string $path): array
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($path));
        $stats = [];

        for ($index = 0; $index < $zip->numFiles; ++$index) {
            $name = $zip->getNameIndex($index);
            self::assertIsString($name);
            $stat = $zip->statName($name);
            self::assertIsArray($stat);
            $stats[$name] = $stat;
        }

        $zip->close();

        return $stats;
    }

    private function removeDirectory(string $directory): void
    {
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.'/'.$entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
