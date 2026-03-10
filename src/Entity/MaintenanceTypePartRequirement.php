<?php

namespace App\Entity;

use App\Repository\MaintenanceTypePartRequirementRepository;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaintenanceTypePartRequirementRepository::class)]
#[ORM\Table(
    name: 'maintenance_type_part_requirement',
    uniqueConstraints: [
        new UniqueConstraint(name: 'uniq_mtp_type_part', columns: ['maintenance_type_id', 'part_id']),
    ]
)]
class MaintenanceTypePartRequirement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'maintenanceTypePartRequirements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MaintenanceType $maintenanceType = null;

    #[ORM\ManyToOne(inversedBy: 'maintenanceTypePartRequirements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Part $part = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $quantityRequired = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isOptional = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaintenanceType(): ?MaintenanceType
    {
        return $this->maintenanceType;
    }

    public function setMaintenanceType(?MaintenanceType $maintenanceType): static
    {
        $this->maintenanceType = $maintenanceType;

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

    public function getQuantityRequired(): ?string
    {
        return $this->quantityRequired;
    }

    public function setQuantityRequired(string $quantityRequired): static
    {
        $this->quantityRequired = $quantityRequired;

        return $this;
    }

    public function isOptional(): ?bool
    {
        return $this->isOptional;
    }

    public function setIsOptional(bool $isOptional): static
    {
        $this->isOptional = $isOptional;

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
