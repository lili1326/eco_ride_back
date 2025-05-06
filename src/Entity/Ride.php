<?php

namespace App\Entity;

use App\Repository\RideRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
 


#[ORM\Entity(repositoryClass: RideRepository::class)]
class Ride
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['ride:read', 'ride:write'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Groups(['ride:read', 'ride:write'])] 
    private ?\DateTimeImmutable $date_depart = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(['ride:read', 'ride:write'])] 
    private ?\DateTimeImmutable $heure_depart = null;

    #[ORM\Column(length: 255)]
    #[Groups(['ride:read', 'ride:write'])]
    private ?string $lieu_depart = null;


    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Groups(['ride:read', 'ride:write'])] 
    private ?\DateTimeImmutable $heure_arrivee = null;
    #[Groups(['ride:read', 'ride:write'])]
    #[ORM\Column(length: 255)]
    
    private ?string $lieu_arrivee = null;
    #[Groups(['ride:read', 'ride:write'])]
    #[ORM\Column(nullable: true)]
  
    private ?int $note_conducteur = null;
    #[Groups(['ride:read', 'ride:write'])]
    #[ORM\Column]
     
    private ?int $nb_place = null;
    #[Groups(['ride:read', 'ride:write'])]
    #[ORM\Column]
     
    private ?int $prix_personne = null;
    #[Groups(['ride:read', 'ride:write'])]
    #[ORM\Column]
     
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['ride:read', 'ride:write'])]
    private ?\DateTimeImmutable $updateAt = null;

    #[ORM\ManyToOne(inversedBy: 'rides')]
    #[ORM\JoinColumn(nullable: false)]
    
    private ?User $conducteur = null;

    /**
     * @var Collection<int, Participe>
     */
    #[ORM\OneToMany(targetEntity: Participe::class, mappedBy: 'covoiturage')]
    private Collection $participes;

    #[ORM\Column(length: 255)]
    #[Groups(['ride:read', 'ride:write'])]
    private ?string $energie = null;

    public function __construct()
    {
        $this->participes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDepart(): ?\DateTimeImmutable
    {
        return $this->date_depart;
    }

    public function setDateDepart(\DateTimeImmutable $date_depart): static
    {
        $this->date_depart = $date_depart;

        return $this;
    }

    public function getHeureDepart(): ?\DateTimeImmutable
    {
        return $this->heure_depart;
    }

    public function setHeureDepart(\DateTimeImmutable $heure_depart): static
    {
        $this->heure_depart = $heure_depart;

        return $this;
    }

    public function getLieuDepart(): ?string
    {
        return $this->lieu_depart;
    }

    public function setLieuDepart(string $lieu_depart): static
    {
        $this->lieu_depart = $lieu_depart;

        return $this;
    }

    

    public function getHeureArrivee(): ?\DateTimeImmutable
    {
        return $this->heure_arrivee;
    }

    public function setHeureArrivee(\DateTimeImmutable $heure_arrivee): static
    {
        $this->heure_arrivee = $heure_arrivee;

        return $this;
    }

    public function getLieuArrivee(): ?string
    {
        return $this->lieu_arrivee;
    }

    public function setLieuArrivee(string $lieu_arrivee): static
    {
        $this->lieu_arrivee = $lieu_arrivee;

        return $this;
    }

    public function getNoteConducteur(): ?int
    {
        return $this->note_conducteur;
    }

    public function setNoteConducteur(?int $note_conducteur): static
    {
        $this->note_conducteur = $note_conducteur;

        return $this;
    }

    public function getNbPlace(): ?int
    {
        return $this->nb_place;
    }

    public function setNbPlace(int $nb_place): static
    {
        $this->nb_place = $nb_place;

        return $this;
    }

    public function getPrixPersonne(): ?int
    {
        return $this->prix_personne;
    }

    public function setPrixPersonne(int $prix_personne): static
    {
        $this->prix_personne = $prix_personne;

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

    public function getConducteur(): ?User
    {
        return $this->conducteur;
    }

    public function setConducteur(?User $conducteur): static
    {
        $this->conducteur = $conducteur;

        return $this;
    }

    /**
     * @return Collection<int, Participe>
     */
    public function getParticipes(): Collection
    {
        return $this->participes;
    }

    public function addParticipe(Participe $participe): static
    {
        if (!$this->participes->contains($participe)) {
            $this->participes->add($participe);
            $participe->setCovoiturage($this);
        }

        return $this;
    }

    public function removeParticipe(Participe $participe): static
    {
        if ($this->participes->removeElement($participe)) {
            // set the owning side to null (unless already changed)
            if ($participe->getCovoiturage() === $this) {
                $participe->setCovoiturage(null);
            }
        }

        return $this;
    }

    public function getEnergie(): ?string
    {
        return $this->energie;
    }

    public function setEnergie(string $energie): static
    {
        $this->energie = $energie;

        return $this;
    }
}