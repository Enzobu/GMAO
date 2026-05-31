<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;

use ApiPlatform\Metadata\Get;

use ApiPlatform\Metadata\GetCollection;

use ApiPlatform\Metadata\Patch;

use ApiPlatform\Metadata\Post;

use Symfony\Component\Serializer\Annotation\Groups;

use App\Repository\AddressRepository;
use App\Security\SecurityExpression;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: SecurityExpression::ROLE_USER),
        new Get(security: SecurityExpression::ROLE_USER),
        new Post(security: SecurityExpression::ROLE_ADMIN),
        new Patch(security: SecurityExpression::ROLE_ADMIN),
    ],
    normalizationContext: ['groups' => ['address:read']],
    denormalizationContext: ['groups' => ['address:write']]
)]
#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['address:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['address:read', 'address:write', 'user:read', 'user:write', 'inspection_center:read', 'inspection_center:write'])]
    private ?string $line1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['address:read', 'address:write', 'user:read', 'user:write', 'inspection_center:read', 'inspection_center:write'])]
    private ?string $line2 = null;

    #[ORM\Column(length: 255)]
    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Le code postal doit contenir 5 chiffres.')]
    #[Groups(['address:read', 'address:write', 'user:read', 'user:write', 'inspection_center:read', 'inspection_center:write'])]
    private ?string $postalCode = null;

    #[ORM\Column(length: 255)]
    #[Groups(['address:read', 'address:write', 'user:read', 'user:write', 'inspection_center:read', 'inspection_center:write'])]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    #[Groups(['address:read', 'address:write', 'user:read', 'user:write', 'inspection_center:read', 'inspection_center:write'])]
    private ?string $country = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLine1(): ?string
    {
        return $this->line1;
    }

    public function setLine1(string $line1): static
    {
        $this->line1 = $line1;

        return $this;
    }

    public function getLine2(): ?string
    {
        return $this->line2;
    }

    public function setLine2(?string $line2): static
    {
        $this->line2 = $line2;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = trim($postalCode);

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }
}
