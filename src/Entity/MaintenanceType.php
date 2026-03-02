<?php

namespace App\Entity;

use App\Repository\MaintenanceTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MaintenanceTypeRepository::class)]
class MaintenanceType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?int $intervalKm = null;

    #[ORM\Column(nullable: true)]
    private ?int $intervalMonths = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /**
     * @var Collection<int, VehicleMaintenance>
     */
    #[ORM\OneToMany(targetEntity: VehicleMaintenance::class, mappedBy: 'maintenanceType')]
    private Collection $vehicleMaintenances;

    /**
     * @var Collection<int, MaintenanceTypePartRequirement>
     */
    #[ORM\OneToMany(targetEntity: MaintenanceTypePartRequirement::class, mappedBy: 'maintenanceType')]
    private Collection $maintenanceTypePartRequirements;

    public function __construct()
    {
        $this->vehicleMaintenances = new ArrayCollection();
        $this->maintenanceTypePartRequirements = new ArrayCollection();
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
        $this->name = $name;

        return $this;
    }

    public function getIntervalKm(): ?int
    {
        return $this->intervalKm;
    }

    public function setIntervalKm(?int $intervalKm): static
    {
        $this->intervalKm = $intervalKm;

        return $this;
    }

    public function getIntervalMonths(): ?int
    {
        return $this->intervalMonths;
    }

    public function setIntervalMonths(?int $intervalMonths): static
    {
        $this->intervalMonths = $intervalMonths;

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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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
            $vehicleMaintenance->setMaintenanceType($this);
        }

        return $this;
    }

    public function removeVehicleMaintenance(VehicleMaintenance $vehicleMaintenance): static
    {
        if ($this->vehicleMaintenances->removeElement($vehicleMaintenance)) {
            // set the owning side to null (unless already changed)
            if ($vehicleMaintenance->getMaintenanceType() === $this) {
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MaintenanceTypePartRequirement>
     */
    public function getMaintenanceTypePartRequirements(): Collection
    {
        return $this->maintenanceTypePartRequirements;
    }

    public function addMaintenanceTypePartRequirement(MaintenanceTypePartRequirement $maintenanceTypePartRequirement): static
    {
        if (!$this->maintenanceTypePartRequirements->contains($maintenanceTypePartRequirement)) {
            $this->maintenanceTypePartRequirements->add($maintenanceTypePartRequirement);
            $maintenanceTypePartRequirement->setMaintenanceType($this);
        }

        return $this;
    }

    public function removeMaintenanceTypePartRequirement(MaintenanceTypePartRequirement $maintenanceTypePartRequirement): static
    {
        if ($this->maintenanceTypePartRequirements->removeElement($maintenanceTypePartRequirement)) {
            // set the owning side to null (unless already changed)
            if ($maintenanceTypePartRequirement->getMaintenanceType() === $this) {
            }
        }

        return $this;
    }
}
