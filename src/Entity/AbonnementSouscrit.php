<?php

namespace App\Entity;

use App\Dto\ValidationResult;
use App\Enum\MoyenPaiement;
use App\Repository\AbonnementSouscritRepository;
use Doctrine\ORM\Mapping as ORM;
use \App\Enum\Statut;
use DateTime;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: AbonnementSouscritRepository::class)]
class AbonnementSouscrit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'abonnementSouscrits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'abonnementSouscrits')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Abonnement $abonnement = null;

    #[ORM\Column(enumType: Statut::class)]
    private ?Statut $statut = null;

    #[ORM\Column]
    private ?bool $tarifReduitVerifie = null;

    #[ORM\Column]
    private ?bool $isTarifReduit = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

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

    public function getAbonnement(): ?Abonnement
    {
        return $this->abonnement;
    }

    public function setAbonnement(?Abonnement $abonnement): static
    {
        $this->abonnement = $abonnement;

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

    public function isValid() : ValidationResult 
    {
        $now = new DateTimeImmutable();
        if ($now < $this->abonnement->getSaison()->getDateDebut()) {
            return new ValidationResult(false, 'La saison n’a pas encore commencé.');
        }
        if ($now > $this->abonnement->getSaison()->getDateFin()) {
            return new ValidationResult(false, 'La saison de l\'abonnement est terminée.');
        }

        if (!$this->abonnement->isActif()) {
            return new ValidationResult(false, 'L’abonnement a été désactivé.');
        }

        if ($this->isTarifReduit() && !$this->tarifReduitVerifie) {
            return new ValidationResult(false, 'L\'abonnement est en attente de vérification du justificatif pour tarif réduit.');
        }
        else if ($this->statut == Statut::PENDING) {
            return new ValidationResult(false, 'Le statut de l’abonnement est en attente.');
        }
        else if ($this->statut != Statut::ACTIVE) {
            return new ValidationResult(false, 'Le statut de l’abonnement n’est pas actif.');
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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
