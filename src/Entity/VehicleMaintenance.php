<?php

namespace App\Entity;

use App\Enum\MaintenanceStatusEnum;
use App\Repository\VehicleMaintenanceRepository;
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

    #[ORM\Column]
    private ?\DateTimeImmutable $performedAt = null;

    #[ORM\Column]
    private ?int $mileage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $cost = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $nextDueDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $nextDueMileage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private ?bool $isPlanned = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(enumType: MaintenanceStatusEnum::class, options: ['default' => 'todo'])]
    private MaintenanceStatusEnum $status = MaintenanceStatusEnum::ToDo;

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

    public function setCost(string $cost): static
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
}
