<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;

use ApiPlatform\Metadata\Get;

use ApiPlatform\Metadata\GetCollection;

use ApiPlatform\Metadata\Patch;

use ApiPlatform\Metadata\Post;

use Symfony\Component\Serializer\Annotation\Groups;

use App\ApiPlatform\State\MaintenanceStateProcessor;
use App\Enum\MaintenanceStatusEnum;
use App\Repository\MaintenanceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER')"),
        new Patch(security: "is_granted('ROLE_ADMIN') or object.getVehicle().getUser() == user"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['maintenance:read']],
    denormalizationContext: ['groups' => ['maintenance:write']],
    processor: MaintenanceStateProcessor::class,
)]
#[ORM\Entity(repositoryClass: MaintenanceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Maintenance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['maintenance:read', 'vehicle:read', 'maintenance_part:read', 'document:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'maintenances')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['maintenance:read', 'maintenance:write'])]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne(inversedBy: 'maintenances')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['maintenance:read', 'maintenance:write', 'vehicle:read'])]
    private ?MaintenanceType $maintenanceType = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['maintenance:read', 'maintenance:write', 'vehicle:read'])]
    private ?int $mileage = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['maintenance:read', 'maintenance:write', 'vehicle:read'])]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['maintenance:read', 'maintenance:write', 'vehicle:read'])]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['maintenance:read', 'maintenance:write', 'vehicle:read'])]
    private ?\DateTimeImmutable $plannedAt = null;

    #[ORM\Column(enumType: MaintenanceStatusEnum::class)]
    #[Groups(['maintenance:read', 'maintenance:write', 'vehicle:read'])]
    private ?MaintenanceStatusEnum $status = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['maintenance:read', 'maintenance:write', 'vehicle:read'])]
    private ?bool $isExternal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['maintenance:read', 'maintenance:write', 'vehicle:read'])]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['maintenance:read', 'maintenance:write', 'vehicle:read'])]
    private ?int $nextDueMileage = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['maintenance:read', 'maintenance:write', 'vehicle:read'])]
    private ?\DateTimeImmutable $nextDueAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['maintenance:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['maintenance:read'])]
    private \DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, MaintenancePart>
     */
    #[ORM\OneToMany(targetEntity: MaintenancePart::class, mappedBy: 'maintenance', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['maintenance:read', 'maintenance:write'])]
    private Collection $maintenanceParts;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['maintenance:read', 'maintenance:write', 'vehicle:read'])]
    private bool $isDeleted = false;

    #[ORM\OneToMany(mappedBy: 'maintenance', targetEntity: Document::class, orphanRemoval: true)]
    #[Groups(['maintenance:read'])]
    private Collection $documents;

    public function __construct()
    {
        $this->maintenanceParts = new ArrayCollection();
        $this->documents = new ArrayCollection();
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

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(?int $mileage): static
    {
        $this->mileage = $mileage;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): static
    {
        $this->finishedAt = $finishedAt;

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

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setMaintenance($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getMaintenance() === $this) {
                $document->setIsDeleted(true);
            }
        }

        return $this;
    }
}
