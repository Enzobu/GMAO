<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;

use ApiPlatform\Metadata\Delete;

use ApiPlatform\Metadata\Get;

use ApiPlatform\Metadata\GetCollection;

use ApiPlatform\Metadata\Patch;

use ApiPlatform\Metadata\Post;

use Symfony\Component\Serializer\Annotation\Groups;

use App\ApiPlatform\State\InspectionCenterStateProcessor;
use App\Repository\InspectionCenterRepository;
use App\Security\SecurityExpression;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            security: SecurityExpression::ROLE_USER,
            paginationEnabled: false,
        ),
        new Get(security: SecurityExpression::ROLE_USER),
        new Post(security: SecurityExpression::ROLE_ADMIN),
        new Patch(security: SecurityExpression::ROLE_ADMIN),
        new Delete(security: SecurityExpression::ROLE_ADMIN),
    ],
    normalizationContext: ['groups' => ['inspection_center:read']],
    denormalizationContext: ['groups' => ['inspection_center:write']],
    processor: InspectionCenterStateProcessor::class,
)]
#[ORM\Entity(repositoryClass: InspectionCenterRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Ce centre de contrôle technique existe déjà.')]
class InspectionCenter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['inspection_center:read', 'vehicle_inspection:read', 'vehicle:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['inspection_center:read', 'inspection_center:write', 'vehicle_inspection:read', 'vehicle:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(pattern: '/^0[1-9]( \d{2}){4}$/', message: 'Le téléphone doit respecter le format 04 85 74 85 96.')]
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

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['inspection_center:read'])]
    private bool $isDeleted = false;

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
        $this->phone = $this->formatPhone($phone);

        return $this;
    }

    private function formatPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            return implode(' ', str_split($digits, 2));
        }

        return trim((string) $phone);
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
        if (
            $this->vehicleInspections->removeElement($vehicleInspection)
            && $vehicleInspection->getCenter() === $this
        ) {
            $vehicleInspection->setCenter(null);
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
