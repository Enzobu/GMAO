<?php

namespace App\Entity;

use App\Repository\PartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartRepository::class)]
#[ORM\Table(
    name: 'part',
    uniqueConstraints: [
        new UniqueConstraint(name: 'uniq_part_brand_reference', columns: ['brand', 'reference']),
    ]
)]
class Part
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $category = null;

    #[ORM\Column(length: 255)]
    private ?string $unit = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $barcode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /**
     * @var Collection<int, InventoryItem>
     */
    #[ORM\OneToMany(targetEntity: InventoryItem::class, mappedBy: 'part')]
    private Collection $inventoryItems;

    /**
     * @var Collection<int, MaintenanceTypePartRequirement>
     */
    #[ORM\OneToMany(targetEntity: MaintenanceTypePartRequirement::class, mappedBy: 'part')]
    private Collection $maintenanceTypePartRequirements;

    /**
     * @var Collection<int, VehicleMaintenancePart>
     */
    #[ORM\OneToMany(targetEntity: VehicleMaintenancePart::class, mappedBy: 'part')]
    private Collection $vehicleMaintenanceParts;

    public function __construct()
    {
        $this->inventoryItems = new ArrayCollection();
        $this->maintenanceTypePartRequirements = new ArrayCollection();
        $this->vehicleMaintenanceParts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function setBarcode(?string $barcode): static
    {
        $this->barcode = $barcode;

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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, InventoryItem>
     */
    public function getInventoryItems(): Collection
    {
        return $this->inventoryItems;
    }

    public function addInventoryItem(InventoryItem $inventoryItem): static
    {
        if (!$this->inventoryItems->contains($inventoryItem)) {
            $this->inventoryItems->add($inventoryItem);
            $inventoryItem->setPart($this);
        }

        return $this;
    }

    public function removeInventoryItem(InventoryItem $inventoryItem): static
    {
        if ($this->inventoryItems->removeElement($inventoryItem)) {
            // set the owning side to null (unless already changed)
            if ($inventoryItem->getPart() === $this) {
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MaintenanceTypePartRequirement>
     */
    public function getMaintenanceTypePartRequirements(): Collection
    {
        return $this->maintenanceTypePartRequirements;
    }

    public function addMaintenanceTypePartRequirement(MaintenanceTypePartRequirement $maintenanceTypePartRequirement): static
    {
        if (!$this->maintenanceTypePartRequirements->contains($maintenanceTypePartRequirement)) {
            $this->maintenanceTypePartRequirements->add($maintenanceTypePartRequirement);
            $maintenanceTypePartRequirement->setPart($this);
        }

        return $this;
    }

    public function removeMaintenanceTypePartRequirement(MaintenanceTypePartRequirement $maintenanceTypePartRequirement): static
    {
        if ($this->maintenanceTypePartRequirements->removeElement($maintenanceTypePartRequirement)) {
            // set the owning side to null (unless already changed)
            if ($maintenanceTypePartRequirement->getPart() === $this) {
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VehicleMaintenancePart>
     */
    public function getVehicleMaintenanceParts(): Collection
    {
        return $this->vehicleMaintenanceParts;
    }

    public function addVehicleMaintenancePart(VehicleMaintenancePart $vehicleMaintenancePart): static
    {
        if (!$this->vehicleMaintenanceParts->contains($vehicleMaintenancePart)) {
            $this->vehicleMaintenanceParts->add($vehicleMaintenancePart);
            $vehicleMaintenancePart->setPart($this);
        }

        return $this;
    }

    public function removeVehicleMaintenancePart(VehicleMaintenancePart $vehicleMaintenancePart): static
    {
        if ($this->vehicleMaintenanceParts->removeElement($vehicleMaintenancePart)) {
            // set the owning side to null (unless already changed)
            if ($vehicleMaintenancePart->getPart() === $this) {
            }
        }

        return $this;
    }
}
