<?php

namespace App\Entity;

use App\Repository\AbonnementSouscritRepository;
use Doctrine\ORM\Mapping as ORM;
use \App\Enum\Statut;

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
}
