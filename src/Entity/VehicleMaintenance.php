<?php

namespace App\Entity;

use App\Enum\MaintenanceStatusEnum;
use App\Repository\VehicleMaintenanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleMaintenanceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class VehicleMaintenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'vehicleMaintenances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne(inversedBy: 'vehicleMaintenances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?MaintenanceType $maintenanceType = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $performedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $mileage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $cost = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $nextDueDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $nextDueMileage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isPlanned = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(enumType: MaintenanceStatusEnum::class, options: ['default' => 'todo'])]
    private MaintenanceStatusEnum $status = MaintenanceStatusEnum::ToDo;

    /**
     * @var Collection<int, VehicleMaintenancePart>
     */
    #[ORM\OneToMany(targetEntity: VehicleMaintenancePart::class, mappedBy: 'vehicleMaintenance')]
    private Collection $vehicleMaintenanceParts;

    public function __construct()
    {
        $this->vehicleMaintenanceParts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): static
    {
        $this->vehicle = $vehicle;

        return $this;
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

    public function getPerformedAt(): ?\DateTimeImmutable
    {
        return $this->performedAt;
    }

    public function setPerformedAt(\DateTimeImmutable $performedAt): static
    {
        $this->performedAt = $performedAt;

        return $this;
    }

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(int $mileage): static
    {
        $this->mileage = $mileage;

        return $this;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(?string $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getNextDueDate(): ?\DateTimeImmutable
    {
        return $this->nextDueDate;
    }

    public function setNextDueDate(?\DateTimeImmutable $nextDueDate): static
    {
        $this->nextDueDate = $nextDueDate;

        return $this;
    }

    public function getNextDueMileage(): ?int
    {
        return $this->nextDueMileage;
    }

    public function setNextDueMileage(?int $nextDueMileage): static
    {
        $this->nextDueMileage = $nextDueMileage;

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

    public function isPlanned(): ?bool
    {
        return $this->isPlanned;
    }

    public function setIsPlanned(bool $isPlanned): static
    {
        $this->isPlanned = $isPlanned;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function onCreate(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getStatus(): ?MaintenanceStatusEnum
    {
        return $this->status;
    }

    public function setStatus(MaintenanceStatusEnum $status): static
    {
        $this->status = $status;

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
            $vehicleMaintenancePart->setVehicleMaintenance($this);
        }

        return $this;
    }

    public function removeVehicleMaintenancePart(VehicleMaintenancePart $vehicleMaintenancePart): static
    {
        if ($this->vehicleMaintenanceParts->removeElement($vehicleMaintenancePart)) {
            // set the owning side to null (unless already changed)
            if ($vehicleMaintenancePart->getVehicleMaintenance() === $this) {
            }
        }

        return $this;
    }
}
