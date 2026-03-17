<?php

namespace App\Entity;

use App\Enum\MaintenanceStatusEnum;
use App\Enum\MaintenanceTypeEnum;
use App\Repository\MaintenanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaintenanceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Maintenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'maintenances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(enumType: MaintenanceTypeEnum::class)]
    private ?MaintenanceTypeEnum $maintenanceType = null;

    #[ORM\Column]
    private ?int $mileage = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $performedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $plannedAt = null;

    #[ORM\Column(enumType: MaintenanceStatusEnum::class)]
    private ?MaintenanceStatusEnum $status = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isExternal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    private ?int $nextDueMileage = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $nextDueAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, MaintenancePart>
     */
    #[ORM\OneToMany(targetEntity: MaintenancePart::class, mappedBy: 'maintenance', cascade: ['persist'])]
    private Collection $maintenanceParts;

    #[ORM\Column(options: ['default' => false])]
    private bool $isDeleted = false;

    public function __construct()
    {
        $this->maintenanceParts = new ArrayCollection();
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

    public function getMaintenanceType(): ?MaintenanceTypeEnum
    {
        return $this->maintenanceType;
    }

    public function setMaintenanceType(MaintenanceTypeEnum $maintenanceType): static
    {
        $this->maintenanceType = $maintenanceType;

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

    public function getPerformedAt(): ?\DateTimeImmutable
    {
        return $this->performedAt;
    }

    public function setPerformedAt(?\DateTimeImmutable $performedAt): static
    {
        $this->performedAt = $performedAt;

        return $this;
    }

    public function getPlannedAt(): ?\DateTimeImmutable
    {
        return $this->plannedAt;
    }

    public function setPlannedAt(?\DateTimeImmutable $plannedAt): static
    {
        $this->plannedAt = $plannedAt;

        return $this;
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

    public function isExternal(): ?bool
    {
        return $this->isExternal;
    }

    public function setIsExternal(bool $isExternal): static
    {
        $this->isExternal = $isExternal;

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

    public function getNextDueMileage(): ?int
    {
        return $this->nextDueMileage;
    }

    public function setNextDueMileage(?int $nextDueMileage): static
    {
        $this->nextDueMileage = $nextDueMileage;

        return $this;
    }

    public function getNextDueAt(): ?\DateTimeImmutable
    {
        return $this->nextDueAt;
    }

    public function setNextDueAt(?\DateTimeImmutable $nextDueAt): static
    {
        $this->nextDueAt = $nextDueAt;

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
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return Collection<int, MaintenancePart>
     */
    public function getMaintenanceParts(): Collection
    {
        return $this->maintenanceParts;
    }

    public function addMaintenancePart(MaintenancePart $maintenancePart): static
    {
        if (!$this->maintenanceParts->contains($maintenancePart)) {
            $this->maintenanceParts->add($maintenancePart);
            $maintenancePart->setMaintenance($this);
        }

        return $this;
    }

    public function removeMaintenancePart(MaintenancePart $maintenancePart): static
    {
        if ($this->maintenanceParts->removeElement($maintenancePart)) {
            // set the owning side to null (unless already changed)
            if ($maintenancePart->getMaintenance() === $this) {
            }
        }

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }
}
