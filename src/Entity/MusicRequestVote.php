<?php

namespace App\Entity;

use App\Repository\MusicRequestVoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MusicRequestVoteRepository::class)]
#[ORM\UniqueConstraint(
    name: 'unique_music_request_vote',
    columns: ['request_id', 'token']
)]
class MusicRequestVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'musicRequestVotes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MusicRequest $request = null;

    #[ORM\Column(length: 255)]
    private ?string $token = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequest(): ?MusicRequest
    {
        return $this->request;
    }

    public function setRequest(?MusicRequest $request): static
    {
        $this->request = $request;

        return $this;
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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}