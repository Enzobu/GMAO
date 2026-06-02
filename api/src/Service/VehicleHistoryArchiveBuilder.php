<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Repository\DocumentRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\VehicleInspectionRepository;
use App\Repository\VehicleInsuranceRepository;
use App\Exception\DocumentStorageException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use ZipArchive;

final readonly class VehicleHistoryArchiveBuilder
{
    private const INFORMATION_FILE = 'informations.md';

    public function __construct(
        private VehicleInsuranceRepository $insurances,
        private VehicleInspectionRepository $inspections,
        private MaintenanceRepository $maintenances,
        private DocumentRepository $documents,
        private DocumentManager $documentManager,
        private VehicleHistoryArchiveFormatter $formatter,
    ) {}

    public function build(Vehicle $vehicle): VehicleHistoryArchive
    {
        $zipPath = $this->temporaryZipPath();
        $zip = new ZipArchive();

        // @codeCoverageIgnoreStart
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new HttpException(500, 'Impossible de créer l’archive.');
        }
        // @codeCoverageIgnoreEnd

        $archiveName = $this->formatter->historyDirectory($vehicle);

        $this->addVehicleHistory($zip, $vehicle);
        $zip->close();

        return new VehicleHistoryArchive(
            $zipPath,
            $archiveName.'.zip',
        );
    }

    private function addVehicleHistory(ZipArchive $zip, Vehicle $vehicle): void
    {
        $this->addDirectory($zip, 'assurances');
        $this->addDirectory($zip, 'controles_technique');
        $this->addDirectory($zip, 'interventions');

        $this->addVehicleDocuments($zip, $vehicle);
        $this->addInsurances($zip, $vehicle);
        $this->addInspections($zip, $vehicle);
        $this->addMaintenances($zip, $vehicle);
    }

    private function addVehicleDocuments(ZipArchive $zip, Vehicle $vehicle): void
    {
        $directory = '';

        $this->addMarkdown($zip, $directory, $this->formatter->vehicleMarkdown($vehicle));
        $this->addDocuments($zip, $directory, $this->documents->findByVehicle($vehicle));
    }

    private function addInsurances(
        ZipArchive $zip,
        Vehicle $vehicle,
    ): void {
        $usedDirectories = [];
        $insurances = $this->insurances->findByVehicle(
            ['vehicle' => $vehicle],
            ['startDate' => 'DESC', 'createdAt' => 'DESC'],
        );

        foreach ($insurances as $insurance) {
            $directory = 'assurances/'.$this->uniqueName(
                $this->formatter->insuranceDirectory($insurance),
                $usedDirectories,
            );
            $eventDate = $this->insuranceDate($insurance);

            $this->addDirectory($zip, $directory, $eventDate);
            $this->addMarkdown($zip, $directory, $this->formatter->insuranceMarkdown($insurance), $eventDate);
            $this->addDocuments(
                $zip,
                $directory,
                $this->documents->findByVehicleInsurance($insurance),
                $eventDate,
            );
        }
    }

    private function addInspections(
        ZipArchive $zip,
        Vehicle $vehicle,
    ): void {
        $usedDirectories = [];
        $inspections = $this->inspections->findByVehicle(
            ['vehicle' => $vehicle],
            ['inspectionDate' => 'DESC', 'createdAt' => 'DESC'],
        );

        foreach ($inspections as $inspection) {
            $date = $inspection->getInspectionDate();
            $directory = 'controles_technique/'.$this->uniqueName(
                $date
                    ? $this->formatter->inspectionDirectory($date)
                    : 'controle_technique_sans_date',
                $usedDirectories,
            );

            $this->addDirectory($zip, $directory, $inspection->getInspectionDate());
            $this->addMarkdown(
                $zip,
                $directory,
                $this->formatter->inspectionMarkdown($inspection),
                $inspection->getInspectionDate(),
            );
            $this->addDocuments(
                $zip,
                $directory,
                $this->documents->findByVehicleInspection($inspection),
                $inspection->getInspectionDate(),
            );
        }
    }

    private function addMaintenances(
        ZipArchive $zip,
        Vehicle $vehicle,
    ): void {
        $usedDirectories = [];

        foreach ($this->maintenances->findForVehicle($vehicle) as $maintenance) {
            $eventDate = $this->maintenanceDate($maintenance);
            $directory = 'interventions/'.$this->uniqueName(
                $this->formatter->maintenanceDirectory($maintenance, $eventDate),
                $usedDirectories,
            );

            $this->addDirectory($zip, $directory, $eventDate);
            $this->addMarkdown($zip, $directory, $this->formatter->maintenanceMarkdown($maintenance), $eventDate);
            $this->addDocuments(
                $zip,
                $directory,
                $this->documents->findByMaintenance($maintenance),
                $eventDate,
            );
        }
    }

    /** @param Document[] $documents */
    private function addDocuments(
        ZipArchive $zip,
        string $directory,
        array $documents,
        ?\DateTimeInterface $mtime = null,
    ): void {
        $usedFilenames = [];

        foreach ($documents as $document) {
            try {
                if (!$this->documentManager->fileExists($document)) {
                    continue;
                }

                $path = $this->documentManager->getAbsolutePath($document);
            } catch (DocumentStorageException) {
                continue;
            }

            $entryPath = $this->formatter->documentPath(
                $directory,
                $this->uniqueName(
                    $this->formatter->documentFilename($document),
                    $usedFilenames,
                ),
            );

            $zip->addFile($path, $entryPath);
            $this->setEntryMtime($zip, $entryPath, $mtime);
        }
    }

    private function addMarkdown(
        ZipArchive $zip,
        string $directory,
        string $content,
        ?\DateTimeInterface $mtime = null,
    ): void {
        $entryPath = $this->formatter->documentPath(
            $directory,
            self::INFORMATION_FILE,
        );

        $zip->addFromString($entryPath, $content);
        $this->setEntryMtime($zip, $entryPath, $mtime);
    }

    private function addDirectory(
        ZipArchive $zip,
        string $directory,
        ?\DateTimeInterface $mtime = null,
    ): void
    {
        $zip->addEmptyDir($directory);
        $this->setEntryMtime($zip, $directory.'/', $mtime);
    }

    private function maintenanceDate(Maintenance $maintenance): ?\DateTimeInterface
    {
        return $maintenance->getFinishedAt()
            ?? $maintenance->getStartedAt()
            ?? $maintenance->getPlannedAt();
    }

    private function insuranceDate(VehicleInsurance $insurance): ?\DateTimeInterface
    {
        return $insurance->getStartDate();
    }

    private function setEntryMtime(
        ZipArchive $zip,
        string $entryPath,
        ?\DateTimeInterface $mtime,
    ): void {
        if ($mtime === null) {
            return;
        }

        $zip->setMtimeName($entryPath, $mtime->getTimestamp());
    }

    /** @param array<string, true> $usedNames */
    private function uniqueName(string $name, array &$usedNames): string
    {
        $candidate = $name;
        $index = 2;

        while (isset($usedNames[$candidate])) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $baseName = $extension === '' ? $name : substr($name, 0, -strlen($extension) - 1);
            $candidate = $extension === ''
                ? sprintf('%s_%d', $baseName, $index)
                : sprintf('%s_%d.%s', $baseName, $index, $extension);
            ++$index;
        }

        $usedNames[$candidate] = true;

        return $candidate;
    }

    private function temporaryZipPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'vehicle-history-');

        // @codeCoverageIgnoreStart
        if ($path === false) {
            throw new HttpException(500, 'Impossible de préparer l’archive.');
        }
        // @codeCoverageIgnoreEnd

        return $path;
    }
}
