<?php

namespace App\Entity;

use App\Repository\PreferenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PreferenceRepository::class)]
class Preference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['preference:read', 'preference:write'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT )]
    #[Groups(['preference:read', 'preference:write','ride:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['preference:read', 'preference:write'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['preference:read', 'preference:write'])]
    private ?\DateTimeImmutable $updateAt = null;

    #[ORM\ManyToOne(inversedBy: 'preferences')]
    private ?User $utilisateur = null;

    #[ORM\Column(length: 255 )]
    #[Groups(['preference:read', 'preference:write'])]
    private ?string $musique = null;

    #[ORM\Column(length: 255)]
    #[Groups(['preference:read', 'preference:write'])]
    private ?string $fumeur = null;

    #[ORM\Column(length: 255)]
    #[Groups(['preference:read', 'preference:write'])]
    private ?string $animaux = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeImmutable
    {
        return $this->updateAt;
    }

    public function setUpdateAt(?\DateTimeImmutable $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getMusique(): ?string
    {
        return $this->musique;
    }

    public function setMusique(?string $musique): static
    {
        $this->musique = $musique;

        return $this;
    }

    public function getFumeur(): ?string
    {
        return $this->fumeur;
    }

    public function setFumeur(string $fumeur): static
    {
        $this->fumeur = $fumeur;

        return $this;
    }

    public function getAnimaux(): ?string
    {
        return $this->animaux;
    }

    public function setAnimaux(string $animaux): static
    {
        $this->animaux = $animaux;

        return $this;
    }
}