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
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresseNumero = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresseRue = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresseCodePostal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresseVille = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 10)]
    private ?DonationStatus $status = DonationStatus::CREATED;

    #[ORM\Column(nullable: true)]
    private ?int $checkoutId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $urlRecuFiscal = null;

    #[ORM\Column(length: 20)]
    private ?TypeDon $TypeDon = null;

    #[ORM\Column(length: 20)]
    private ?MoyenPaiement $moyenPaiement = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adressePays = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateDeNaissance = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getAdresseNumero(): ?string
    {
        return $this->adresseNumero;
    }

    public function setAdresseNumero(?string $adresseNumero): static
    {
        $this->adresseNumero = $adresseNumero;

        return $this;
    }

    public function getAdresseRue(): ?string
    {
        return $this->adresseRue;
    }

    public function setAdresseRue(?string $adresseRue): static
    {
        $this->adresseRue = $adresseRue;

        return $this;
    }

    public function getAdresseCodePostal(): ?string
    {
        return $this->adresseCodePostal;
    }

    public function setAdresseCodePostal(?string $adresseCodePostal): static
    {
        $this->adresseCodePostal = $adresseCodePostal;

        return $this;
    }

    public function getAdresseVille(): ?string
    {
        return $this->adresseVille;
    }

    public function setAdresseVille(?string $adresseVille): static
    {
        $this->adresseVille = $adresseVille;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(DonationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCheckoutId(): ?int
    {
        return $this->checkoutId;
    }

    public function setCheckoutId(int $checkoutId): static
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

    public function getTypeDon(): ?TypeDon
    {
        return $this->TypeDon;
    }

    public function setTypeDon(TypeDon $TypeDon): static
    {
        $this->TypeDon = $TypeDon;

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

    public function getAdressePays(): ?string
    {
        return $this->adressePays;
    }

    public function setAdressePays(?string $adressePays): static
    {
        $this->adressePays = $adressePays;

        return $this;
    }

    public function getDateDeNaissance(): ?DateTimeImmutable
    {
        return $this->dateDeNaissance;
    }

    public function setDateDeNaissance(\DateTimeImmutable $dateDeNaissance): static
    {
        $this->dateDeNaissance = $dateDeNaissance;

        return $this;
    }
}
