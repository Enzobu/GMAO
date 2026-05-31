<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;

use ApiPlatform\Metadata\Delete;

use ApiPlatform\Metadata\Get;

use ApiPlatform\Metadata\GetCollection;

use ApiPlatform\Metadata\Patch;

use ApiPlatform\Metadata\Post;

use Symfony\Component\Serializer\Annotation\Groups;

use App\ApiPlatform\State\VehicleStateProcessor;
use App\Enum\VehicleColorEnum;
use App\Enum\VehicleFuelTypeEnum;
use App\Enum\VehicleStatusEnum;
use App\Enum\VehicleTransmissionTypeEnum;
use App\Enum\VehicleTypeEnum;
use App\Repository\VehicleRepository;
use App\Security\SecurityExpression;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: SecurityExpression::ROLE_USER),
        new Get(security: SecurityExpression::ROLE_USER),
        new Post(security: SecurityExpression::ROLE_USER),
        new Patch(security: SecurityExpression::ADMIN_OR_VEHICLE_OWNER),
        new Delete(security: SecurityExpression::ROLE_ADMIN),
    ],
    normalizationContext: ['groups' => ['vehicle:read']],
    denormalizationContext: ['groups' => ['vehicle:write']],
    processor: VehicleStateProcessor::class,
)]
#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['vehicle:read', 'maintenance:read', 'vehicle_insurance:read', 'vehicle_inspection:read', 'document:read', 'part:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['vehicle:read', 'vehicle:write', 'maintenance:read', 'vehicle_insurance:read', 'vehicle_inspection:read', 'document:read', 'part:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Assert\Regex(pattern: '/^[A-Z]{2}-\d{3}-[A-Z]{2}$/', message: 'La plaque d’immatriculation doit respecter le format AA-123-AA.')]
    #[Groups(['vehicle:read', 'vehicle:write', 'maintenance:read', 'vehicle_insurance:read', 'vehicle_inspection:read', 'document:read', 'part:read'])]
    private ?string $registration = null;

    #[ORM\Column(length: 100)]
    #[Groups(['vehicle:read', 'vehicle:write', 'maintenance:read', 'vehicle_insurance:read', 'vehicle_inspection:read', 'document:read', 'part:read'])]
    private ?string $brand = null;

    #[ORM\Column(length: 150)]
    #[Groups(['vehicle:read', 'vehicle:write', 'maintenance:read', 'vehicle_insurance:read', 'vehicle_inspection:read', 'document:read', 'part:read'])]
    private ?string $model = null;

    #[ORM\Column(enumType: VehicleTypeEnum::class, nullable: true)]
    #[Groups(['vehicle:read', 'vehicle:write', 'maintenance:read', 'vehicle_insurance:read', 'vehicle_inspection:read', 'document:read', 'part:read'])]
    private ?VehicleTypeEnum $type = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(notInRangeMessage: 'L’année doit être comprise entre {{ min }} et {{ max }}.', min: 1800, max: 2100)]
    #[Groups(['vehicle:read', 'vehicle:write'])]
    private ?int $year = null;

    #[ORM\Column(length: 17, nullable: true, unique: true)]
    #[Assert\Length(max: 17, maxMessage: 'Le VIN ne peut pas dépasser {{ limit }} caractères.')]
    #[Groups(['vehicle:read', 'vehicle:write'])]
    private ?string $vin = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['vehicle:read', 'vehicle:write'])]
    private ?string $engine = null;

    #[ORM\Column(enumType: VehicleFuelTypeEnum::class, nullable: true)]
    #[Groups(['vehicle:read', 'vehicle:write'])]
    private ?VehicleFuelTypeEnum $fuelType = null;

    #[ORM\Column(enumType: VehicleTransmissionTypeEnum::class, nullable: true)]
    #[Groups(['vehicle:read', 'vehicle:write'])]
    private ?VehicleTransmissionTypeEnum $transmission = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le kilométrage ne peut pas être négatif.')]
    #[Groups(['vehicle:read', 'vehicle:write', 'maintenance:read', 'vehicle_insurance:read', 'vehicle_inspection:read'])]
    private ?int $lastMileage = null;

    #[ORM\Column(enumType: VehicleColorEnum::class, nullable: true)]
    #[Groups(['vehicle:read', 'vehicle:write'])]
    private ?VehicleColorEnum $color = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Assert\Range(notInRangeMessage: 'La date doit être comprise entre le 01/01/1800 et le 31/12/2100.', min: '1800-01-01', max: '2100-12-31')]
    #[Groups(['vehicle:read', 'vehicle:write'])]
    private ?\DateTimeImmutable $purchaseDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le prix d’achat ne peut pas être négatif.')]
    #[Groups(['vehicle:read', 'vehicle:write'])]
    private ?string $purchasePrice = null;

    #[ORM\Column(enumType: VehicleStatusEnum::class, options: ['default' => VehicleStatusEnum::Active->value])]
    #[Groups(['vehicle:read', 'vehicle:write', 'maintenance:read', 'vehicle_insurance:read', 'vehicle_inspection:read'])]
    private VehicleStatusEnum $status = VehicleStatusEnum::Active;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['vehicle:read', 'vehicle:write'])]
    private ?User $user = null;

    /**
     * @var Collection<int, VehicleInsurance>
     */
    #[ORM\OneToMany(targetEntity: VehicleInsurance::class, mappedBy: 'vehicle')]
    #[Groups(['vehicle:read'])]
    private Collection $vehicleInsurances;

    /**
     * @var Collection<int, VehicleInspection>
     */
    #[ORM\OneToMany(targetEntity: VehicleInspection::class, mappedBy: 'vehicle')]
    #[Groups(['vehicle:read'])]
    private Collection $vehicleInspections;

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: Document::class, orphanRemoval: true)]
    private Collection $documents;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['vehicle:read', 'vehicle:write'])]
    private bool $isDeleted = false;

    /**
     * @var Collection<int, Part>
     */
    #[ORM\ManyToMany(targetEntity: Part::class, mappedBy: 'vehicles')]
    #[Groups(['vehicle:read'])]
    private Collection $parts;

    /**
     * @var Collection<int, Maintenance>
     */
    #[ORM\OneToMany(targetEntity: Maintenance::class, mappedBy: 'vehicle')]
    #[Groups(['vehicle:read'])]
    private Collection $maintenances;

    public function __construct()
    {
        $this->vehicleInsurances = new ArrayCollection();
        $this->vehicleInspections = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->parts = new ArrayCollection();
        $this->maintenances = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = strtolower($name);

        return $this;
    }

    public function getRegistration(): ?string
    {
        return $this->registration;
    }

    public function setRegistration(string $registration): static
    {
        $this->registration = $this->formatRegistration($registration);

        return $this;
    }

    public function displayName(): ?string
    {
        return ucfirst($this->name) . ' ・ ' . $this->registration;
    }

    private function formatRegistration(string $registration): string
    {
        $value = strtoupper(trim($registration));
        $compactValue = preg_replace('/[^A-Z0-9]/', '', $value) ?? '';

        if (preg_match('/^([A-Z]{2})(\d{3})([A-Z]{2})$/', $compactValue, $matches) === 1) {
            return sprintf('%s-%s-%s', $matches[1], $matches[2], $matches[3]);
        }

        return $value;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = strtolower($brand);

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = strtolower($model);

        return $this;
    }

    public function getType(): ?VehicleTypeEnum
    {
        return $this->type;
    }

    public function setType(?VehicleTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getVin(): ?string
    {
        return $this->vin;
    }

    public function setVin(?string $vin): static
    {
        $vin = $vin !== null ? strtoupper(trim($vin)) : null;
        $this->vin = $vin !== '' ? $vin : null;

        return $this;
    }

    public function getEngine(): ?string
    {
        return $this->engine;
    }

    public function setEngine(?string $engine): static
    {
        $this->engine = $engine;

        return $this;
    }

    public function getFuelType(): ?VehicleFuelTypeEnum
    {
        return $this->fuelType;
    }

    public function setFuelType(?VehicleFuelTypeEnum $fuelType): static
    {
        $this->fuelType = $fuelType;

        return $this;
    }

    public function getTransmission(): ?VehicleTransmissionTypeEnum
    {
        return $this->transmission;
    }

    public function setTransmission(?VehicleTransmissionTypeEnum $transmission): static
    {
        $this->transmission = $transmission;

        return $this;
    }

    public function getLastMileage(): ?int
    {
        return $this->lastMileage;
    }

    public function setLastMileage(?int $lastMileage): static
    {
        $this->lastMileage = $lastMileage;

        return $this;
    }

    public function getColor(): ?VehicleColorEnum
    {
        return $this->color;
    }

    public function setColor(?VehicleColorEnum $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeImmutable
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(?\DateTimeImmutable $purchaseDate): static
    {
        $this->purchaseDate = $purchaseDate;

        return $this;
    }

    public function getPurchasePrice(): ?string
    {
        return $this->purchasePrice;
    }

    public function setPurchasePrice(?string $purchasePrice): static
    {
        $this->purchasePrice = $purchasePrice;

        return $this;
    }

    public function getStatus(): ?VehicleStatusEnum
    {
        return $this->status;
    }

    public function setStatus(VehicleStatusEnum $status): static
    {
        $this->status = $status;

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

    /**
     * @return Collection<int, VehicleInsurance>
     */
    public function getVehicleInsurances(): Collection
    {
        return $this->vehicleInsurances->filter(static fn (VehicleInsurance $insurance): bool => !$insurance->isDeleted());
    }

    public function addVehicleInsurance(VehicleInsurance $vehicleInsurance): static
    {
        if (!$this->vehicleInsurances->contains($vehicleInsurance)) {
            $this->vehicleInsurances->add($vehicleInsurance);
            $vehicleInsurance->setVehicle($this);
        }

        return $this;
    }

    public function removeVehicleInsurance(VehicleInsurance $vehicleInsurance): static
    {
        $this->vehicleInsurances->removeElement($vehicleInsurance);

        return $this;
    }

    /**
     * @return Collection<int, VehicleInspection>
     */
    public function getVehicleInspections(): Collection
    {
        return $this->vehicleInspections->filter(static fn (VehicleInspection $inspection): bool => !$inspection->isDeleted());
    }

    public function addVehicleInspection(VehicleInspection $vehicleInspection): static
    {
        if (!$this->vehicleInspections->contains($vehicleInspection)) {
            $this->vehicleInspections->add($vehicleInspection);
            $vehicleInspection->setVehicle($this);
        }

        return $this;
    }

    public function removeVehicleInspection(VehicleInspection $vehicleInspection): static
    {
        if (
            $this->vehicleInspections->removeElement($vehicleInspection)
            && $vehicleInspection->getVehicle() === $this
        ) {
            $vehicleInspection->setVehicle(null);
        }

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
            $document->setVehicle($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if (
            $this->documents->removeElement($document)
            && $document->getVehicle() === $this
        ) {
            $document->setIsDeleted(true);
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
     * @return Collection<int, Part>
     */
    public function getParts(): Collection
    {
        return $this->parts;
    }

    public function addPart(Part $part): static
    {
        if (!$this->parts->contains($part)) {
            $this->parts->add($part);
            $part->addVehicle($this);
        }

        return $this;
    }

    public function removePart(Part $part): static
    {
        if ($this->parts->removeElement($part)) {
            $part->removeVehicle($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Maintenance>
     */
    public function getMaintenances(): Collection
    {
        return $this->maintenances->filter(static fn (Maintenance $maintenance): bool => !$maintenance->isDeleted());
    }

    public function addMaintenance(Maintenance $maintenance): static
    {
        if (!$this->maintenances->contains($maintenance)) {
            $this->maintenances->add($maintenance);
            $maintenance->setVehicle($this);
        }

        return $this;
    }

    public function removeMaintenance(Maintenance $maintenance): static
    {
        if (
            $this->maintenances->removeElement($maintenance)
            && $maintenance->getVehicle() === $this
        ) {
            $maintenance->setVehicle(null);
        }

        return $this;
    }
}
