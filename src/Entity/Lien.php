<?php

namespace App\Entity;

use App\Repository\LienRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LienRepository::class)]
class Lien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private ?string $token = null;

    #[ORM\Column(length: 255)]
    private ?string $urlCible = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::JSON)]
    private array $clics = [];

    #[ORM\Column(length: 255)]
    private ?string $qrCodePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoPath = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->token = bin2hex(random_bytes(6)); // ou un token base62 si tu préfères
        $this->clics = [];
    }

    public function ajouterClic(): void
    {
        $this->clics[] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
    }

    public function getClics(): array
    {
        return array_map(
            fn($isoDate) => new \DateTimeImmutable($isoDate),
            $this->clics
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getUrlCible(): ?string
    {
        return $this->urlCible;
    }

    public function setUrlCible(string $urlCible): static
    {
        $this->urlCible = $urlCible;

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

    public function getQrCodePath(): ?string
    {
        return $this->qrCodePath;
    }

    public function setQrCodePath(string $qrCodePath): static
    {
        $this->qrCodePath = $qrCodePath;

        return $this;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): static
    {
        $this->logoPath = $logoPath;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
