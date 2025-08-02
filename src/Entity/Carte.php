<?php

namespace App\Entity;

use App\Repository\CarteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarteRepository::class)]
class Carte
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column]
    private ?int $nombreSeances = null;

    #[ORM\Column]
    private ?float $tarif = null;

    #[ORM\Column(nullable: true)]
    private ?float $tarifReduit = null;

    #[ORM\Column]
    private ?bool $hasTarifReduit = null;

    #[ORM\Column]
    private ?bool $isActif = null;

    public function FunctionName()
    {
        $this->isActif = true;
    }

    /**
     * @var Collection<int, CarteSouscrite>
     */
    #[ORM\OneToMany(targetEntity: CarteSouscrite::class, mappedBy: 'carte', orphanRemoval: true)]
    private Collection $carteSouscrites;

    /**
     * @var Collection<int, Discipline>
     */
    #[ORM\ManyToMany(targetEntity: Discipline::class)]
    private Collection $disciplines;

    public function __construct()
    {
        $this->carteSouscrites = new ArrayCollection();
        $this->hasTarifReduit = false;
        $this->isActif = false;
        $this->disciplines = new ArrayCollection();
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

    public function getNombreSeances(): ?int
    {
        return $this->nombreSeances;
    }

    public function setNombreSeances(int $nombreSeances): static
    {
        $this->nombreSeances = $nombreSeances;

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

    /**
     * @return Collection<int, CarteSouscrite>
     */
    public function getCarteSouscrites(): Collection
    {
        return $this->carteSouscrites;
    }

    public function addCarteSouscrite(CarteSouscrite $carteSouscrite): static
    {
        if (!$this->carteSouscrites->contains($carteSouscrite)) {
            $this->carteSouscrites->add($carteSouscrite);
            $carteSouscrite->setCarte($this);
        }

        return $this;
    }

    public function removeCarteSouscrite(CarteSouscrite $carteSouscrite): static
    {
        if ($this->carteSouscrites->removeElement($carteSouscrite)) {
            // set the owning side to null (unless already changed)
            if ($carteSouscrite->getCarte() === $this) {
                $carteSouscrite->setCarte(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Discipline>
     */
    public function getDisciplines(): Collection
    {
        return $this->disciplines;
    }
}
