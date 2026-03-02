<?php

namespace App\Entity;

use App\Repository\InventoryItemRepository;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(
    name: 'inventory_item',
    uniqueConstraints: [
        new UniqueConstraint(name: 'uniq_inventory_part', columns: ['part_id']),
    ]
)]
class InventoryItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'inventoryItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Part $part = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $quantity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $minQuantity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $averageUnitCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $lastPurchaseUnitCost = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPart(): ?Part
    {
        return $this->part;
    }

    public function setPart(Part $part): static
    {
        $this->part = $part;

        return $this;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getMinQuantity(): ?string
    {
        return $this->minQuantity;
    }

    public function setMinQuantity(?string $minQuantity): static
    {
        $this->minQuantity = $minQuantity;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getAverageUnitCost(): ?string
    {
        return $this->averageUnitCost;
    }

    public function setAverageUnitCost(?string $averageUnitCost): static
    {
        $this->averageUnitCost = $averageUnitCost;

        return $this;
    }

    public function getLastPurchaseUnitCost(): ?string
    {
        return $this->lastPurchaseUnitCost;
    }

    public function setLastPurchaseUnitCost(?string $lastPurchaseUnitCost): static
    {
        $this->lastPurchaseUnitCost = $lastPurchaseUnitCost;

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
    #[ORM\PreUpdate]
    public function touchUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
