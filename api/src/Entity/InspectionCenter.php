<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;

use ApiPlatform\Metadata\Get;

use ApiPlatform\Metadata\GetCollection;

use ApiPlatform\Metadata\Patch;

use ApiPlatform\Metadata\Post;

use Symfony\Component\Serializer\Annotation\Groups;

use App\Repository\InspectionCenterRepository;
use App\Security\SecurityExpression;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new GetCollection(security: SecurityExpression::ROLE_USER),
        new Get(security: SecurityExpression::ROLE_USER),
        new Post(security: SecurityExpression::ROLE_ADMIN),
        new Patch(security: SecurityExpression::ROLE_ADMIN),
    ],
    normalizationContext: ['groups' => ['inspection_center:read']],
    denormalizationContext: ['groups' => ['inspection_center:write']]
)]
#[ORM\Entity(repositoryClass: InspectionCenterRepository::class)]
class InspectionCenter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['inspection_center:read', 'vehicle_inspection:read', 'vehicle:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['inspection_center:read', 'inspection_center:write', 'vehicle_inspection:read', 'vehicle:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['inspection_center:read', 'inspection_center:write', 'vehicle_inspection:read'])]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['inspection_center:read', 'inspection_center:write', 'vehicle_inspection:read'])]
    private ?string $email = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['inspection_center:read', 'inspection_center:write'])]
    private ?Address $address = null;

    /**
     * @var Collection<int, VehicleInspection>
     */
    #[ORM\OneToMany(targetEntity: VehicleInspection::class, mappedBy: 'center')]
    #[Groups(['inspection_center:read'])]
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
            if ($vehicleInspection->getCenter() === $this) {
                $vehicleInspection->setCenter(null);
            }
        }

        return $this;
    }
}
