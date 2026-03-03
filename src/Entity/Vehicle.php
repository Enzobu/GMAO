<?php

namespace App\Entity;

use App\Enum\VehicleFuelTypeEnum;
use App\Enum\VehicleStatusEnum;
use App\Enum\VehicleTransmissionTypeEnum;
use App\Enum\VehicleTypeEnum;
use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $registration = null;

    #[ORM\Column(length: 100)]
    private ?string $brand = null;

    #[ORM\Column(length: 150)]
    private ?string $model = null;

    #[ORM\Column(enumType: VehicleTypeEnum::class, nullable: true)]
    private ?VehicleTypeEnum $type = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $year = null;

    #[ORM\Column(length: 17, nullable: true, unique: true)]
    private ?string $vin = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $engine = null;

    #[ORM\Column(enumType: VehicleFuelTypeEnum::class, nullable: true)]
    private ?VehicleFuelTypeEnum $fuelType = null;

    #[ORM\Column(enumType: VehicleTransmissionTypeEnum::class, nullable: true)]
    private ?VehicleTransmissionTypeEnum $transmission = null;

    #[ORM\Column(nullable: true)]
    private ?int $lastMileage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $purchaseDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $purchasePrice = null;

    #[ORM\Column(enumType: VehicleStatusEnum::class, options: ['default' => VehicleStatusEnum::Active->value])]
    private VehicleStatusEnum $status = VehicleStatusEnum::Active;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, VehicleInsurance>
     */
    #[ORM\OneToMany(targetEntity: VehicleInsurance::class, mappedBy: 'vehicle')]
    private Collection $vehicleInsurances;

    /**
     * @var Collection<int, VehicleInspection>
     */
    #[ORM\OneToMany(targetEntity: VehicleInspection::class, mappedBy: 'vehicle')]
    private Collection $vehicleInspections;

    /**
     * @var Collection<int, VehicleMaintenance>
     */
    #[ORM\OneToMany(targetEntity: VehicleMaintenance::class, mappedBy: 'vehicle')]
    private Collection $vehicleMaintenances;

    public function __construct()
    {
        $this->vehicleInsurances = new ArrayCollection();
        $this->vehicleInspections = new ArrayCollection();
        $this->vehicleMaintenances = new ArrayCollection();
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
        $this->registration = strtolower($registration);

        return $this;
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
        $this->vin = $vin;

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

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = strtolower($color);

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
        return $this->vehicleInsurances;
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
        if ($this->vehicleInsurances->removeElement($vehicleInsurance)) {
            // set the owning side to null (unless already changed)
            if ($vehicleInsurance->getVehicle() === $this) {
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VehicleInspection>
     */
    public function getVehicleInspections(): Collection
    {
        return $this->vehicleInspections;
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
        if ($this->vehicleInspections->removeElement($vehicleInspection)) {
            // set the owning side to null (unless already changed)
            if ($vehicleInspection->getVehicle() === $this) {
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VehicleMaintenance>
     */
    public function getVehicleMaintenances(): Collection
    {
        return $this->vehicleMaintenances;
    }

    public function addVehicleMaintenance(VehicleMaintenance $vehicleMaintenance): static
    {
        if (!$this->vehicleMaintenances->contains($vehicleMaintenance)) {
            $this->vehicleMaintenances->add($vehicleMaintenance);
            $vehicleMaintenance->setVehicle($this);
        }

        return $this;
    }

    public function removeVehicleMaintenance(VehicleMaintenance $vehicleMaintenance): static
    {
        if ($this->vehicleMaintenances->removeElement($vehicleMaintenance)) {
            // set the owning side to null (unless already changed)
            if ($vehicleMaintenance->getVehicle() === $this) {
                $vehicleMaintenance->setVehicle(null);
            }
        }

        return $this;
    }
}
