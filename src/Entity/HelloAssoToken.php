<?php

namespace App\Entity;

use App\Repository\HelloAssoTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HelloAssoTokenRepository::class)]
class HelloAssoToken
{
    #[ORM\Id]
    #[ORM\Column]
    private ?int $id = 1;

    #[ORM\Column(length: 1024)]
    private ?string $accessToken = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $accessTokenExpiresAt = null;

    #[ORM\Column(length: 1024)]
    private ?string $refreshToken = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $refreshTokenExpiresAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getAccessTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->accessTokenExpiresAt;
    }

    public function setAccessTokenExpiresAt(\DateTimeImmutable $accessTokenExpiresAt): static
    {
        $this->accessTokenExpiresAt = $accessTokenExpiresAt;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): static
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getRefreshTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->refreshTokenExpiresAt;
    }

    public function setRefreshTokenExpiresAt(\DateTimeImmutable $refreshTokenExpiresAt): static
    {
        $this->refreshTokenExpiresAt = $refreshTokenExpiresAt;

        return $this;
    }

    public function isAccessTokenValid(): bool
    {
        return $this->accessTokenExpiresAt > new \DateTimeImmutable();
    }

    public function isRefreshTokenValid(): bool
    {
        return $this->refreshTokenExpiresAt > new \DateTimeImmutable();
    }
}
