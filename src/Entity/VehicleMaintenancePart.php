<?php

namespace App\Entity;

use App\Repository\VehicleMaintenancePartRepository;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleMaintenancePartRepository::class)]
#[ORM\Table(
    name: 'vehicle_maintenance_part',
    uniqueConstraints: [
        new UniqueConstraint(name: 'uniq_vmp_maintenance_part', columns: ['vehicle_maintenance_id', 'part_id']),
    ]
)]
class VehicleMaintenancePart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'vehicleMaintenanceParts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?VehicleMaintenance $vehicleMaintenance = null;

    #[ORM\ManyToOne(inversedBy: 'vehicleMaintenanceParts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Part $part = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $quantityUsed = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $unitPrice = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $fromStock = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVehicleMaintenance(): ?VehicleMaintenance
    {
        return $this->vehicleMaintenance;
    }

    public function setVehicleMaintenance(?VehicleMaintenance $vehicleMaintenance): static
    {
        $this->vehicleMaintenance = $vehicleMaintenance;

        return $this;
    }

    public function getPart(): ?Part
    {
        return $this->part;
    }

    public function setPart(?Part $part): static
    {
        $this->part = $part;

        return $this;
    }

    public function getQuantityUsed(): ?string
    {
        return $this->quantityUsed;
    }

    public function setQuantityUsed(string $quantityUsed): static
    {
        $this->quantityUsed = $quantityUsed;

        return $this;
    }

    public function getUnitPrice(): ?string
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(?string $unitPrice): static
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function isFromStock(): bool
    {
        return $this->fromStock;
    }

    public function setFromStock(bool $fromStock): static
    {
        $this->fromStock = $fromStock;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
