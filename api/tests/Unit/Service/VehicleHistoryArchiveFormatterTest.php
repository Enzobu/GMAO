<?php

namespace App\Tests\Unit\Service;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Enum\InsurancePaymentFrequencyEnum;
use App\Entity\VehicleInspection;
use App\Enum\InspectionResultEnum;
use App\Service\VehicleHistoryArchiveFormatter;
use PHPUnit\Framework\TestCase;

final class VehicleHistoryArchiveFormatterTest extends TestCase
{
    public function testFormatsFallbackValues(): void
    {
        $formatter = new VehicleHistoryArchiveFormatter();
        $vehicle = (new Vehicle())
            ->setName('  ')
            ->setRegistration('')
            ->setBrand('')
            ->setModel('');
        $maintenance = (new Maintenance())->setVehicle($vehicle);
        $inspection = (new VehicleInspection())
            ->setVehicle($vehicle)
            ->setResult(InspectionResultEnum::Pass);

        self::assertSame(
            'historique_Non_renseigné_SANS-IMMAT',
            $formatter->historyDirectory($vehicle),
        );
        self::assertSame(
            'intervention_Non_renseigné_Entretien',
            $formatter->maintenanceDirectory($maintenance, null),
        );
        self::assertStringContainsString(
            '- Date du contrôle: Non renseigné',
            $formatter->inspectionMarkdown($inspection),
        );
        self::assertStringContainsString(
            '- Kilométrage: Non renseigné',
            $formatter->vehicleMarkdown($vehicle),
        );
        self::assertStringContainsString(
            '- Véhicule: Non renseigné',
            $formatter->insuranceMarkdown($this->insuranceWithoutVehicle()),
        );
    }

    public function testFormatsDocumentsWithoutExtensionOrSafeName(): void
    {
        $formatter = new VehicleHistoryArchiveFormatter();
        $documentWithoutExtension = (new Document())
            ->setName('document')
            ->setOriginalFilename('document');
        $unsafeDocument = (new Document())
            ->setName('///')
            ->setOriginalFilename('///');

        self::assertSame(
            'document.bin',
            $formatter->documentFilename($documentWithoutExtension),
        );
        self::assertSame(
            'bin',
            $formatter->documentFilename($unsafeDocument),
        );
        self::assertSame(
            'folder/file.pdf',
            $formatter->documentPath('folder', 'file.pdf'),
        );
    }

    private function insuranceWithoutVehicle(): VehicleInsurance
    {
        return (new VehicleInsurance())
            ->setProviderName('')
            ->setStartDate(new \DateTimeImmutable('2026-01-01'))
            ->setPaymentFrequency(InsurancePaymentFrequencyEnum::Monthly);
    }
}
