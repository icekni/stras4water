<?php

namespace App\Entity;

use App\Enum\MusicRequestStatus;
use App\Enum\MusicRequestValidationType;
use App\Repository\MusicRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MusicRequestRepository::class)]
class MusicRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    private ?string $artiste = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $youtubeUrl = null;

    #[ORM\Column]
    private int $votes = 0;

    #[ORM\Column(length: 20)]
    private MusicRequestStatus $status = MusicRequestStatus::PENDING;

    #[ORM\Column(length: 30, nullable: true)]
    private ?MusicRequestValidationType $validationType = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Donation $donation = null;

    /**
     * @var Collection<int, MusicRequestVote>
     */
    #[ORM\OneToMany(targetEntity: MusicRequestVote::class, mappedBy: 'request', orphanRemoval: true)]
    private Collection $musicRequestVotes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->musicRequestVotes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getArtiste(): ?string
    {
        return $this->artiste;
    }

    public function setArtiste(string $artiste): static
    {
        $this->artiste = $artiste;

        return $this;
    }

    public function getYoutubeUrl(): ?string
    {
        return $this->youtubeUrl;
    }

    public function setYoutubeUrl(?string $youtubeUrl): static
    {
        $this->youtubeUrl = $youtubeUrl;

        return $this;
    }

    public function getVotes(): int
    {
        return $this->votes;
    }

    public function setVotes(int $votes): static
    {
        $this->votes = $votes;

        return $this;
    }

    public function getStatus(): MusicRequestStatus
    {
        return $this->status;
    }

    public function setStatus(MusicRequestStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getValidationType(): ?MusicRequestValidationType
    {
        return $this->validationType;
    }

    public function setValidationType(?MusicRequestValidationType $validationType): static
    {
        $this->validationType = $validationType;

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

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): static
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    public function getDonation(): ?Donation
    {
        return $this->donation;
    }

    public function setDonation(?Donation $donation): static
    {
        $this->donation = $donation;

        return $this;
    }

    /**
     * @return Collection<int, MusicRequestVote>
     */
    public function getMusicRequestVotes(): Collection
    {
        return $this->musicRequestVotes;
    }

    public function addVote(MusicRequestVote $vote): static
    {
        if (!$this->musicRequestVotes->contains($vote)) {
            $this->musicRequestVotes->add($vote);
            $vote->setRequest($this);
        }

        return $this;
    }

    public function removeVote(MusicRequestVote $vote): static
    {
        if ($this->musicRequestVotes->removeElement($vote)) {
            if ($vote->getRequest() === $this) {
                $vote->setRequest(null);
            }
        }

        return $this;
    }
}