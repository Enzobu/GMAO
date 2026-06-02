<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Entity\VehicleInspection;

final readonly class VehicleHistoryArchiveFormatter
{
    private const UNKNOWN_VALUE = 'Non renseigné';

    public function historyDirectory(Vehicle $vehicle): string
    {
        return $this->sanitize(sprintf(
            'historique_%s_%s',
            $this->vehicleName($vehicle),
            $this->registration($vehicle),
        ));
    }

    public function insuranceDirectory(VehicleInsurance $insurance): string
    {
        $parts = ['assurance', $this->display($insurance->getProviderName())];

        if ($insurance->getPolicyNumber()) {
            $parts[] = $insurance->getPolicyNumber();
        }

        return $this->sanitize(implode('_', $parts));
    }

    public function inspectionDirectory(\DateTimeInterface $date): string
    {
        return $this->sanitize('controle_technique_'.$this->date($date));
    }

    public function maintenanceDirectory(
        Maintenance $maintenance,
        ?\DateTimeInterface $date,
    ): string {
        return $this->sanitize(sprintf(
            'intervention_%s_%s',
            $this->date($date),
            $this->display($maintenance->getMaintenanceType()?->getName() ?? 'entretien'),
        ));
    }

    public function documentFilename(Document $document): string
    {
        $sourceName = $document->getOriginalFilename() ?: $document->getName();
        $extension = strtolower(pathinfo($sourceName, PATHINFO_EXTENSION));
        $baseName = pathinfo($sourceName, PATHINFO_FILENAME) ?: $document->getName();

        return $this->sanitize(sprintf('%s.%s', $baseName, $extension ?: 'bin'));
    }

    public function documentPath(string $directory, string $filename): string
    {
        return $directory === '' ? $filename : $directory.'/'.$filename;
    }

    public function insuranceMarkdown(VehicleInsurance $insurance): string
    {
        return $this->markdown('Assurance '.$this->display($insurance->getProviderName()), [
            'Véhicule' => $this->vehicleLabel($insurance->getVehicle()),
            'Assureur' => $this->display($insurance->getProviderName()),
            'Numéro de police' => $this->value($insurance->getPolicyNumber()),
            'Début' => $this->date($insurance->getStartDate()),
            'Fin' => $this->date($insurance->getEndDate()),
            'Fréquence' => $insurance->getPaymentFrequency()->label(),
            'Statut' => $insurance->isActive() ? 'Active' : 'Inactive',
        ]);
    }

    public function inspectionMarkdown(VehicleInspection $inspection): string
    {
        return $this->markdown('Contrôle technique '.$this->date($inspection->getInspectionDate()), [
            'Véhicule' => $this->vehicleLabel($inspection->getVehicle()),
            'Date du contrôle' => $this->date($inspection->getInspectionDate()),
            'Validité' => $this->date($inspection->getValidUntil()),
            'Kilométrage' => $this->number($inspection->getMileage(), ' km'),
            'Résultat' => $inspection->getResult()?->label() ?? self::UNKNOWN_VALUE,
            'Contre-visite requise' => $inspection->isCounterVisitRequired() ? 'Oui' : 'Non',
            'Date limite contre-visite' => $this->date($inspection->getCounterVisitDueAt()),
            'Centre' => $this->value($inspection->getCenter()?->getName()),
            'Notes' => $this->value($inspection->getNotes()),
        ]);
    }

    public function maintenanceMarkdown(Maintenance $maintenance): string
    {
        $type = $this->display($maintenance->getMaintenanceType()?->getName() ?? 'entretien');

        return $this->markdown('Intervention '.$type, [
            'Véhicule' => $this->vehicleLabel($maintenance->getVehicle()),
            'Type' => $type,
            'Statut' => $maintenance->getStatus()?->label() ?? self::UNKNOWN_VALUE,
            'Date prévue' => $this->date($maintenance->getPlannedAt()),
            'Date de début' => $this->date($maintenance->getStartedAt()),
            'Date de fin' => $this->date($maintenance->getFinishedAt()),
            'Kilométrage' => $this->number($maintenance->getMileage(), ' km'),
            'Intervention externe' => $maintenance->isExternal() ? 'Oui' : 'Non',
            'Prochaine échéance date' => $this->date($maintenance->getNextDueAt()),
            'Prochaine échéance km' => $this->number($maintenance->getNextDueMileage(), ' km'),
            'Notes' => $this->value($maintenance->getNotes()),
        ]);
    }

    public function vehicleMarkdown(Vehicle $vehicle): string
    {
        return $this->markdown('Véhicule '.$this->vehicleName($vehicle), [
            'Nom' => $this->vehicleName($vehicle),
            'Marque' => $this->display($vehicle->getBrand() ?? ''),
            'Modèle' => $this->display($vehicle->getModel() ?? ''),
            'Immatriculation' => $this->registration($vehicle),
            'Année' => $this->number($vehicle->getYear(), ''),
            'Kilométrage' => $this->number($vehicle->getLastMileage(), ' km'),
            'Date d’achat' => $this->date($vehicle->getPurchaseDate()),
        ]);
    }

    public function date(?\DateTimeInterface $date): string
    {
        return $date?->format('d-m-Y') ?? self::UNKNOWN_VALUE;
    }

    private function markdown(string $title, array $items): string
    {
        $lines = ['# '.$title, ''];

        foreach ($items as $label => $value) {
            $lines[] = sprintf('- %s: %s', $label, $value);
        }

        return implode("\n", $lines)."\n";
    }

    private function vehicleLabel(?Vehicle $vehicle): string
    {
        if (!$vehicle) {
            return self::UNKNOWN_VALUE;
        }

        return sprintf('%s - %s', $this->vehicleName($vehicle), $this->registration($vehicle));
    }

    private function vehicleName(Vehicle $vehicle): string
    {
        return $this->display($vehicle->getName() ?: 'Vehicule');
    }

    private function registration(Vehicle $vehicle): string
    {
        return strtoupper($vehicle->getRegistration() ?: 'SANS-IMMAT');
    }

    private function display(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return self::UNKNOWN_VALUE;
        }

        return ucfirst($trimmed);
    }

    private function value(?string $value): string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? self::UNKNOWN_VALUE : $trimmed;
    }

    private function number(?int $value, string $suffix): string
    {
        if ($value === null) {
            return self::UNKNOWN_VALUE;
        }

        return number_format($value, 0, ',', ' ').$suffix;
    }

    private function sanitize(string $value): string
    {
        $value = preg_replace('/[\\/:*?"<>|]+/', '_', trim($value)) ?? '';
        $value = preg_replace('/\s+/', '_', $value) ?? '';
        $value = preg_replace('/_+/', '_', $value) ?? '';

        return trim($value, '._') ?: 'sans_nom';
    }
}
