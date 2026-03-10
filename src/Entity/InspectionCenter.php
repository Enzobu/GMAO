<?php

namespace App\Entity;

use App\Repository\InspectionCenterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InspectionCenterRepository::class)]
class InspectionCenter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Address $address = null;

    /**
     * @var Collection<int, VehicleInspection>
     */
    #[ORM\OneToMany(targetEntity: VehicleInspection::class, mappedBy: 'center')]
    private Collection $vehicleInspections;

    public function __construct()
    {
        $this->vehicleInspections = new ArrayCollection();
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(Address $address): static
    {
        $this->address = $address;

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
            $vehicleInspection->setCenter($this);
        }

        return $this;
    }

    public function removeVehicleInspection(VehicleInspection $vehicleInspection): static
    {
        if ($this->vehicleInspections->removeElement($vehicleInspection)) {
            // set the owning side to null (unless already changed)
            if ($vehicleInspection->getCenter() === $this) {
            }
        }

        return $this;
    }
}
