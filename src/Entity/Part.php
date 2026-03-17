<?php

namespace App\Entity;

use App\Repository\PartRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Part
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'parts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PartType $partType = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private ?int $quantity = null;

    /**
     * @var Collection<int, Vehicle>
     */
    #[ORM\ManyToMany(targetEntity: Vehicle::class, inversedBy: 'parts')]
    private Collection $vehicles;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isDeleted = false;

    #[ORM\OneToMany(mappedBy: 'part', targetEntity: Document::class, orphanRemoval: true)]
    private Collection $documents;

    /**
     * @var Collection<int, MaintenancePart>
     */
    #[ORM\OneToMany(targetEntity: MaintenancePart::class, mappedBy: 'part')]
    private Collection $maintenanceParts;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->maintenanceParts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPartType(): ?PartType
    {
        return $this->partType;
    }

    public function setPartType(?PartType $partType): static
    {
        $this->partType = $partType;

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

    /**
     * @return Collection<int, Vehicle>
     */
    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): static
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles->add($vehicle);
        }

        return $this;
    }

    public function removeVehicle(Vehicle $vehicle): static
    {
        $this->vehicles->removeElement($vehicle);

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

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
    public function setCreatedAtValue(): void
    {
        $now = new \DateTimeImmutable();

        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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
            $document->setPart($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getPart() === $this) {
                $document->setPart(null);
            }
        }

        return $this;
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
            $maintenancePart->setPart($this);
        }

        return $this;
    }

    public function removeMaintenancePart(MaintenancePart $maintenancePart): static
    {
        if ($this->maintenanceParts->removeElement($maintenancePart)) {
            // set the owning side to null (unless already changed)
            if ($maintenancePart->getPart() === $this) {
            }
        }

        return $this;
    }

}
