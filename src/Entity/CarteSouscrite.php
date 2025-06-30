<?php

namespace App\Entity;

use App\Repository\CarteSouscriteRepository;
use Doctrine\ORM\Mapping as ORM;
use \App\Enum\Statut;

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
    private ?int $seancesRestantes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

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
}
