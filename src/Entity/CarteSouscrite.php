<?php

namespace App\Entity;

use App\Dto\ValidationResult;
use App\Enum\MoyenPaiement;
use App\Repository\CarteSouscriteRepository;
use Doctrine\ORM\Mapping as ORM;
use \App\Enum\Statut;
use DateTime;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: CarteSouscriteRepository::class)]
class CarteSouscrite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'carteSouscrites')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'carteSouscrites')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Carte $carte = null;

    #[ORM\Column]
    private ?int $seancesRestantes = 10;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(enumType: Statut::class)]
    private ?Statut $statut = null;

    #[ORM\Column]
    private ?bool $tarifReduitVerifie = null;

    #[ORM\Column]
    private ?bool $isTarifReduit = null;

    #[ORM\Column(enumType: MoyenPaiement::class)]
    private ?MoyenPaiement $moyenPaiement = null;

    public function __construct()
    {
        $this->statut = Statut::CREATED;
        $this->createdAt = new DateTimeImmutable();
        $this->isTarifReduit = false;
        $this->tarifReduitVerifie = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCarte(): ?Carte
    {
        return $this->carte;
    }

    public function setCarte(?Carte $carte): static
    {
        $this->carte = $carte;

        return $this;
    }

    public function getSeancesRestantes(): ?int
    {
        return $this->seancesRestantes;
    }

    public function setSeancesRestantes(int $seancesRestantes): static
    {
        $this->seancesRestantes = $seancesRestantes;

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

    public function getStatut(): ?Statut
    {
        return $this->statut;
    }

    public function setStatut(Statut $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function isValid (): ValidationResult
    {
        if ($this->seancesRestantes <= 0) {
            return new ValidationResult(false, 'La carte de cours est épuisée.');
        }

        if (!$this->carte->isActif()) {
            return new ValidationResult(false, 'La carte a été désactivée.');
        }

        return new ValidationResult(true);
    }

    public function isTarifReduitVerifie(): ?bool
    {
        return $this->tarifReduitVerifie;
    }

    public function setTarifReduitVerifie(bool $tarifReduitVerifie): static
    {
        $this->tarifReduitVerifie = $tarifReduitVerifie;

        return $this;
    }

    public function isTarifReduit(): ?bool
    {
        return $this->isTarifReduit;
    }

    public function setIsTarifReduit(bool $isTarifReduit): static
    {
        $this->isTarifReduit = $isTarifReduit;

        return $this;
    }

    public function getMoyenPaiement(): ?MoyenPaiement
    {
        return $this->moyenPaiement;
    }

    public function setMoyenPaiement(MoyenPaiement $moyenPaiement): static
    {
        $this->moyenPaiement = $moyenPaiement;

        return $this;
    }
}
