<?php

namespace App\Entity;

use App\Repository\ParticipeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParticipeRepository::class)]
#[ORM\Table(name: 'participe')]
class Participe
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $utilisateur = null;

    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'passagers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ride $covoiturage = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): self
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getCovoiturage(): ?Ride
    {
        return $this->covoiturage;
    }

    public function setCovoiturage(?Ride $covoiturage): self
    {
        $this->covoiturage = $covoiturage;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }
}