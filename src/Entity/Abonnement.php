<?php

namespace App\Entity;

use App\Repository\AbonnementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbonnementRepository::class)]
class Abonnement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $validFrom = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $validUntil = null;

    #[ORM\Column]
    private ?float $tarif = null;

    #[ORM\Column(nullable: true)]
    private ?float $tarifReduit = null;

    #[ORM\Column]
    private ?bool $hasTarifReduit = null;

    #[ORM\Column]
    private ?bool $isActif = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Discipline $discipline = null;

    /**
     * @var Collection<int, AbonnementSouscrit>
     */
    #[ORM\OneToMany(targetEntity: AbonnementSouscrit::class, mappedBy: 'abonnement', orphanRemoval: true)]
    private Collection $abonnementSouscrits;

    public function __construct()
    {
        $this->abonnementSouscrits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getValidFrom(): ?\DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(\DateTimeImmutable $validFrom): static
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getValidUntil(): ?\DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function setValidUntil(\DateTimeImmutable $validUntil): static
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    public function getTarif(): ?float
    {
        return $this->tarif;
    }

    public function setTarif(float $tarif): static
    {
        $this->tarif = $tarif;

        return $this;
    }

    public function getTarifReduit(): ?float
    {
        return $this->tarifReduit;
    }

    public function setTarifReduit(?float $tarifReduit): static
    {
        $this->tarifReduit = $tarifReduit;

        return $this;
    }

    public function hasTarifReduit(): ?bool
    {
        return $this->hasTarifReduit;
    }

    public function setHasTarifReduit(bool $hasTarifReduit): static
    {
        $this->hasTarifReduit = $hasTarifReduit;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->isActif;
    }

    public function setIsActif(bool $isActif): static
    {
        $this->isActif = $isActif;

        return $this;
    }

    public function getDiscipline(): ?Discipline
    {
        return $this->discipline;
    }

    public function setDiscipline(?Discipline $discipline): static
    {
        $this->discipline = $discipline;

        return $this;
    }

    /**
     * @return Collection<int, AbonnementSouscrit>
     */
    public function getAbonnementSouscrits(): Collection
    {
        return $this->abonnementSouscrits;
    }

    public function addAbonnementSouscrit(AbonnementSouscrit $abonnementSouscrit): static
    {
        if (!$this->abonnementSouscrits->contains($abonnementSouscrit)) {
            $this->abonnementSouscrits->add($abonnementSouscrit);
            $abonnementSouscrit->setAbonnement($this);
        }

        return $this;
    }

    public function removeAbonnementSouscrit(AbonnementSouscrit $abonnementSouscrit): static
    {
        if ($this->abonnementSouscrits->removeElement($abonnementSouscrit)) {
            // set the owning side to null (unless already changed)
            if ($abonnementSouscrit->getAbonnement() === $this) {
                $abonnementSouscrit->setAbonnement(null);
            }
        }

        return $this;
    }
}
