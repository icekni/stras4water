<?php

namespace App\Entity;

use App\Enum\DonationStatus;
use App\Enum\MoyenPaiement;
use App\Enum\TypeDon;
use App\Repository\DonationRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DonationRepository::class)]
class Donation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $montant = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 10)]
    private ?DonationStatus $status = DonationStatus::CREATED;

    #[ORM\Column(nullable: true)]
    private ?string $checkoutId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $urlRecuFiscal = null;

    #[ORM\ManyToOne(inversedBy: 'donations')]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column]
    private ?bool $wantsRecuFiscal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(enumType: MoyenPaiement::class)]
    private ?MoyenPaiement $moyenPaiement = null;

    #[ORM\Column(enumType: TypeDon::class)]
    private ?TypeDon $typeDon = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeroOrdreRF = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->wantsRecuFiscal = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCreatedAt(DateTimeImmutable $date): static
    {
        $this->createdAt = $date;

        return $this;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(float $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStatus(): ?DonationStatus
    {
        return $this->status;
    }

    public function setStatus(DonationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCheckoutId(): ?string
    {
        return $this->checkoutId;
    }

    public function setCheckoutId(string $checkoutId): static
    {
        $this->checkoutId = $checkoutId;

        return $this;
    }

    public function getUrlRecuFiscal(): ?string
    {
        return $this->urlRecuFiscal;
    }

    public function setUrlRecuFiscal(?string $urlRecuFiscal): static
    {
        $this->urlRecuFiscal = $urlRecuFiscal;

        return $this;
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

    public function getEmail(): ?string
    {
        if ($this->user) {
            return $this->user->getEmail();
        }
        else {
            return $this->email;
        }
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function isWantsRecuFiscal(): ?bool
    {
        return $this->wantsRecuFiscal;
    }

    public function setWantsRecuFiscal(bool $wantRecuFiscal): static
    {
        $this->wantsRecuFiscal = $wantRecuFiscal;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getMontantNet(): ?float
    {
        return $this->moyenPaiement == MoyenPaiement::CARTE 
            ? $this->getMontant() - (($this->getMontant() * 0.015) + 0.25)
            : $this->getMontant();
;
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

    public function getTypeDon(): ?TypeDon
    {
        return $this->typeDon;
    }

    public function setTypeDon(TypeDon $typeDon): static
    {
        $this->typeDon = $typeDon;

        return $this;
    }

    public function getNumeroOrdreRF(): ?string
    {
        return $this->numeroOrdreRF;
    }

    public function setNumeroOrdreRF(?string $numeroOrdreRF): static
    {
        $this->numeroOrdreRF = $numeroOrdreRF;

        return $this;
    }
}
