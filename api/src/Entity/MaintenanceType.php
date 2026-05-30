<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;

use ApiPlatform\Metadata\Delete;

use ApiPlatform\Metadata\Get;

use ApiPlatform\Metadata\GetCollection;

use ApiPlatform\Metadata\Patch;

use ApiPlatform\Metadata\Post;

use Symfony\Component\Serializer\Annotation\Groups;

use App\ApiPlatform\State\MaintenanceTypeStateProcessor;
use App\Repository\MaintenanceTypeRepository;
use App\Security\SecurityExpression;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(
    operations: [
        new GetCollection(security: SecurityExpression::ROLE_USER),
        new Get(security: SecurityExpression::ROLE_USER),
        new Post(security: SecurityExpression::ROLE_ADMIN),
        new Patch(security: SecurityExpression::ROLE_ADMIN),
        new Delete(security: SecurityExpression::ROLE_ADMIN),
    ],
    normalizationContext: ['groups' => ['maintenance_type:read']],
    denormalizationContext: ['groups' => ['maintenance_type:write']],
    processor: MaintenanceTypeStateProcessor::class,
)]
#[ORM\Entity(repositoryClass: MaintenanceTypeRepository::class)]
class MaintenanceType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['maintenance_type:read', 'maintenance:read', 'vehicle:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['maintenance_type:read', 'maintenance_type:write', 'maintenance:read', 'vehicle:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['maintenance_type:read', 'maintenance_type:write'])]
    private ?string $description = null;

    /**
     * @var Collection<int, Maintenance>
     */
    #[ORM\OneToMany(targetEntity: Maintenance::class, mappedBy: 'maintenanceType')]
    #[Groups(['maintenance_type:read'])]
    private Collection $maintenances;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['maintenance_type:read', 'maintenance_type:write'])]
    private bool $isDeleted = false;

    public function __construct()
    {
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
        $this->name = $name;

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

    /**
     * @return Collection<int, Maintenance>
     */
    public function getMaintenances(): Collection
    {
        return $this->maintenances;
    }

    public function addMaintenance(Maintenance $maintenance): static
    {
        if (!$this->maintenances->contains($maintenance)) {
            $this->maintenances->add($maintenance);
            $maintenance->setMaintenanceType($this);
        }

        return $this;
    }

    public function removeMaintenance(Maintenance $maintenance): static
    {
        if (
            $this->maintenances->removeElement($maintenance)
            && $maintenance->getMaintenanceType() === $this
        ) {
            $maintenance->setMaintenanceType(null);
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
