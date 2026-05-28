<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;

use ApiPlatform\Metadata\Get;

use ApiPlatform\Metadata\GetCollection;

use ApiPlatform\Metadata\Patch;

use ApiPlatform\Metadata\Post;

use Symfony\Component\Serializer\Annotation\Groups;

use App\Repository\DocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['document:read']],
    denormalizationContext: ['groups' => ['document:write']]
)]
#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['document:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['document:read', 'document:write'])]
    private string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['document:read'])]
    private ?string $originalFilename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $storedFilename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deletedStoredFilename = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $mimeType = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['document:read'])]
    private ?int $size = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $extension = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['document:read', 'document:write'])]
    private bool $isDeleted = false;

    #[ORM\Column(length: 36, unique: true)]
    #[Groups(['document:read'])]
    private ?string $publicId = null;

    /*
    |----------------------------------------
    | Relations parent (une seule doit exister)
    |----------------------------------------
    */

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[Groups(['document:read', 'document:write'])]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[Groups(['document:read', 'document:write'])]
    private ?VehicleInsurance $vehicleInsurance = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[Groups(['document:read', 'document:write'])]
    private ?VehicleInspection $vehicleInspection = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[Groups(['document:read', 'document:write'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[Groups(['document:read', 'document:write'])]
    private ?Part $part = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[Groups(['document:read', 'document:write'])]
    private ?Maintenance $maintenance = null;

    /*
    |----------------------------------------
    | Timestamps
    |----------------------------------------
    */

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['document:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['document:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->publicId = Uuid::v4()->toRfc4122();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /*
    |----------------------------------------
    | GETTERS / SETTERS
    |----------------------------------------
    */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(?string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;
        return $this;
    }

    public function getStoredFilename(): ?string
    {
        return $this->storedFilename;
    }

    public function setStoredFilename(?string $storedFilename): static
    {
        $this->storedFilename = $storedFilename;
        return $this;
    }

    public function getDeletedStoredFilename(): ?string
    {
        return $this->deletedStoredFilename;
    }

    public function setDeletedStoredFilename(?string $deletedStoredFilename): static
    {
        $this->deletedStoredFilename = $deletedStoredFilename;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): static
    {
        $this->extension = $extension;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
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

    public function getVehicleInsurance(): ?VehicleInsurance
    {
        return $this->vehicleInsurance;
    }

    public function setVehicleInsurance(?VehicleInsurance $vehicleInsurance): static
    {
        $this->vehicleInsurance = $vehicleInsurance;
        return $this;
    }

    public function getVehicleInspection(): ?VehicleInspection
    {
        return $this->vehicleInspection;
    }

    public function setVehicleInspection(?VehicleInspection $vehicleInspection): static
    {
        $this->vehicleInspection = $vehicleInspection;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
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

    public function getMaintenance(): ?Maintenance
    {
        return $this->maintenance;
    }

    public function setMaintenance(?Maintenance $maintenance): static
    {
        $this->maintenance = $maintenance;
        return $this;
    }
    
    public function getParent(): Vehicle|VehicleInsurance|VehicleInspection|User|Part|Maintenance|null
    {
        return $this->vehicle
            ?? $this->vehicleInsurance
            ?? $this->vehicleInspection
            ?? $this->user
            ?? $this->part
            ?? $this->maintenance;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
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

    public function getPublicId(): ?string
    {
        return $this->publicId;
    }

    public function setPublicId(?string $publicId): static
    {
        $this->publicId = $publicId;

        return $this;
    }
}
