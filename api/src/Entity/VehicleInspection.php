<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;

use ApiPlatform\Metadata\Delete;

use ApiPlatform\Metadata\Get;

use ApiPlatform\Metadata\GetCollection;

use ApiPlatform\Metadata\Patch;

use ApiPlatform\Metadata\Post;

use Symfony\Component\Serializer\Annotation\Groups;

use App\ApiPlatform\State\VehicleInspectionStateProcessor;
use App\Enum\InspectionResultEnum;
use App\Repository\VehicleInspectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_USER')"),
        new Patch(security: "is_granted('ROLE_ADMIN') or object.getVehicle().getUser() == user"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['vehicle_inspection:read']],
    denormalizationContext: ['groups' => ['vehicle_inspection:write']],
    processor: VehicleInspectionStateProcessor::class,
)]
#[ORM\Entity(repositoryClass: VehicleInspectionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class VehicleInspection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['vehicle_inspection:read', 'vehicle:read', 'document:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'vehicleInspections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['vehicle_inspection:read', 'vehicle_inspection:write'])]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['vehicle_inspection:read', 'vehicle_inspection:write', 'vehicle:read'])]
    private ?\DateTimeImmutable $inspectionDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['vehicle_inspection:read', 'vehicle_inspection:write', 'vehicle:read'])]
    private ?\DateTimeImmutable $validUntil = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['vehicle_inspection:read', 'vehicle_inspection:write', 'vehicle:read'])]
    private ?int $mileage = null;

    #[ORM\Column(enumType: InspectionResultEnum::class)]
    #[Groups(['vehicle_inspection:read', 'vehicle_inspection:write', 'vehicle:read'])]
    private InspectionResultEnum $result;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['vehicle_inspection:read', 'vehicle_inspection:write', 'vehicle:read'])]
    private bool $counterVisitRequired = false;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['vehicle_inspection:read', 'vehicle_inspection:write', 'vehicle:read'])]
    private ?\DateTimeImmutable $counterVisitDueAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['vehicle_inspection:read', 'vehicle_inspection:write', 'vehicle:read'])]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['vehicle_inspection:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['vehicle_inspection:read'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(inversedBy: 'vehicleInspections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['vehicle_inspection:read', 'vehicle_inspection:write', 'vehicle:read'])]
    private ?InspectionCenter $center = null;

    #[ORM\OneToMany(mappedBy: 'vehicleInspection', targetEntity: Document::class, orphanRemoval: true)]
    #[Groups(['vehicle_inspection:read'])]
    private Collection $documents;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['vehicle_inspection:read', 'vehicle_inspection:write', 'vehicle:read'])]
    private bool $isDeleted = false;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ($this->inspectionDate && $this->validUntil) {
            if ($this->validUntil < $this->inspectionDate) {
                $context->buildViolation('La date de fin de validité doit être après la date du contrôle.')
                    ->atPath('validUntil')
                    ->addViolation();
            }
        }

        if ($this->counterVisitRequired && $this->counterVisitDueAt === null) {
            $context->buildViolation('La date limite de contre-visite est obligatoire si une contre-visite est requise.')
                ->atPath('counterVisitDueAt')
                ->addViolation();
        }
    }

    public function __construct()
    {
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

    public function getInspectionDate(): ?\DateTimeImmutable
    {
        return $this->inspectionDate;
    }

    public function setInspectionDate(\DateTimeImmutable $inspectionDate): static
    {
        $this->inspectionDate = $inspectionDate;

        return $this;
    }

    public function getValidUntil(): ?\DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function setValidUntil(\DateTimeImmutable $validUntil): static
    {
        $this->validUntil = $validUntil;

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

    public function getResult(): ?InspectionResultEnum
    {
        return $this->result;
    }

    public function setResult(InspectionResultEnum $result): static
    {
        $this->result = $result;

        return $this;
    }

    public function isCounterVisitRequired(): bool
    {
        return $this->counterVisitRequired;
    }

    public function setCounterVisitRequired(bool $counterVisitRequired): static
    {
        $this->counterVisitRequired = $counterVisitRequired;

        return $this;
    }

    public function getCounterVisitDueAt(): ?\DateTimeImmutable
    {
        return $this->counterVisitDueAt;
    }

    public function setCounterVisitDueAt(?\DateTimeImmutable $counterVisitDueAt): static
    {
        $this->counterVisitDueAt = $counterVisitDueAt;

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

    public function getCenter(): ?InspectionCenter
    {
        return $this->center;
    }

    public function setCenter(?InspectionCenter $center): static
    {
        $this->center = $center;

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
            $document->setVehicleInspection($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getVehicleInspection() === $this) {
                $document->setIsDeleted(true);
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
