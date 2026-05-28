<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;

use ApiPlatform\Metadata\Get;

use ApiPlatform\Metadata\GetCollection;

use ApiPlatform\Metadata\Patch;

use ApiPlatform\Metadata\Post;

use Symfony\Component\Serializer\Annotation\Groups;

use App\Repository\MaintenancePartRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['maintenance_part:read']],
    denormalizationContext: ['groups' => ['maintenance_part:write']]
)]
#[ORM\Entity(repositoryClass: MaintenancePartRepository::class)]
#[ORM\HasLifecycleCallbacks]
class MaintenancePart
{
    use TimestampableEntityTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['maintenance_part:read', 'maintenance:read', 'maintenance:write'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'maintenanceParts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['maintenance_part:read', 'maintenance_part:write'])]
    private ?Maintenance $maintenance = null;

    #[ORM\ManyToOne(inversedBy: 'maintenanceParts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['maintenance_part:read', 'maintenance_part:write', 'maintenance:read', 'maintenance:write'])]
    private ?Part $part = null;

    #[ORM\Column]
    #[Groups(['maintenance_part:read', 'maintenance_part:write', 'maintenance:read', 'maintenance:write'])]
    private ?int $quantity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['maintenance_part:read', 'maintenance_part:write', 'maintenance:read', 'maintenance:write'])]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['maintenance_part:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['maintenance_part:read'])]
    private \DateTimeImmutable $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPart(): ?Part
    {
        return $this->part;
    }

    public function setPart(?Part $part): static
    {
        $this->part = $part;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

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

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->initializeTimestamps();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->refreshUpdatedAt();
    }
}
