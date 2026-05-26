<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;

use ApiPlatform\Metadata\Delete;

use ApiPlatform\Metadata\Get;

use ApiPlatform\Metadata\GetCollection;

use ApiPlatform\Metadata\Patch;

use ApiPlatform\Metadata\Post;

use Symfony\Component\Serializer\Annotation\Groups;

use App\ApiPlatform\State\VehicleInsuranceStateProcessor;
use App\Enum\InsurancePaymentFrequencyEnum;
use App\Repository\VehicleInsuranceRepository;
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
    normalizationContext: ['groups' => ['vehicle_insurance:read']],
    denormalizationContext: ['groups' => ['vehicle_insurance:write']],
    processor: VehicleInsuranceStateProcessor::class,
)]
#[ORM\Entity(repositoryClass: VehicleInsuranceRepository::class)]
#[ORM\HasLifecycleCallbacks]
class VehicleInsurance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['vehicle_insurance:read', 'vehicle:read', 'document:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'vehicleInsurances')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['vehicle_insurance:read', 'vehicle_insurance:write'])]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(length: 255)]
    #[Groups(['vehicle_insurance:read', 'vehicle_insurance:write', 'vehicle:read'])]
    private string $providerName;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['vehicle_insurance:read', 'vehicle_insurance:write', 'vehicle:read'])]
    private ?string $policyNumber = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['vehicle_insurance:read', 'vehicle_insurance:write', 'vehicle:read'])]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['vehicle_insurance:read', 'vehicle_insurance:write', 'vehicle:read'])]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(enumType: InsurancePaymentFrequencyEnum::class)]
    #[Groups(['vehicle_insurance:read', 'vehicle_insurance:write', 'vehicle:read'])]
    private InsurancePaymentFrequencyEnum $paymentFrequency = InsurancePaymentFrequencyEnum::Monthly;

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['vehicle_insurance:read', 'vehicle:read'])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['vehicle_insurance:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['vehicle_insurance:read'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'vehicleInsurance', targetEntity: Document::class, orphanRemoval: true)]
    #[Groups(['vehicle_insurance:read'])]
    private Collection $documents;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['vehicle_insurance:read', 'vehicle_insurance:write', 'vehicle:read'])]
    private bool $isDeleted = false;

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

    public function setVehicle(Vehicle $vehicle): static
    {
        $this->vehicle = $vehicle;

        return $this;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function setProviderName(string $providerName): static
    {
        $this->providerName = $providerName;

        return $this;
    }

    public function getPolicyNumber(): ?string
    {
        return $this->policyNumber;
    }

    public function setPolicyNumber(?string $policyNumber): static
    {
        $this->policyNumber = $policyNumber;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getPaymentFrequency(): InsurancePaymentFrequencyEnum
    {
        return $this->paymentFrequency;
    }

    public function setPaymentFrequency(InsurancePaymentFrequencyEnum $paymentFrequency): static
    {
        $this->paymentFrequency = $paymentFrequency;

        return $this;
    }

    public function isActive(): bool
    {
        if ($this->endDate === null) {
            return true;
        }

        return $this->endDate > new \DateTimeImmutable('today');
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
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

    #[ORM\PrePersist]
    public function onCreate(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
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

    #[ORM\PreUpdate]
    public function onUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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
            $document->setVehicleInsurance($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            if ($document->getVehicleInsurance() === $this) {
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
