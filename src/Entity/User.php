<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private ?bool $isVerified = null;

    /**
     * @var Collection<int, Donation>
     */
    #[ORM\OneToMany(targetEntity: Donation::class, mappedBy: 'user')]
    private Collection $donations;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column]
    private ?bool $isAdherent = null;

    /**
     * @var Collection<int, AbonnementSouscrit>
     */
    #[ORM\OneToMany(targetEntity: AbonnementSouscrit::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $abonnementSouscrits;

    /**
     * @var Collection<int, CarteSouscrite>
     */
    #[ORM\OneToMany(targetEntity: CarteSouscrite::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $carteSouscrites;

    /**
     * @var Collection<int, SeanceEssai>
     */
    #[ORM\OneToMany(targetEntity: SeanceEssai::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $seanceEssais;

    public function __construct()
    {
        $this->donations = new ArrayCollection();
        $this->abonnementSouscrits = new ArrayCollection();
        $this->carteSouscrites = new ArrayCollection();
        $this->seanceEssais = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * @return Collection<int, Donation>
     */
    public function getDonations(): Collection
    {
        return $this->donations;
    }

    public function addDonation(Donation $donation): static
    {
        if (!$this->donations->contains($donation)) {
            $this->donations->add($donation);
            $donation->setUser($this);
        }

        return $this;
    }

    public function removeDonation(Donation $donation): static
    {
        if ($this->donations->removeElement($donation)) {
            // set the owning side to null (unless already changed)
            if ($donation->getUser() === $this) {
                $donation->setUser(null);
            }
        }

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

    public function isAdherent(): ?bool
    {
        return $this->isAdherent;
    }

    public function setIsAdherent(bool $isAdherent): static
    {
        $this->isAdherent = $isAdherent;

        return $this;
    }

    /**
     * @return Collection<int, AbonnementSouscrit>
     */
    public function getAbonnementSouscrits(): Collection
    {
        return $this->abonnementSouscrits;
    }

    public function addAbonnementSouscrit(AbonnementSouscrit $abonnementSouscrit): static
    {
        if (!$this->abonnementSouscrits->contains($abonnementSouscrit)) {
            $this->abonnementSouscrits->add($abonnementSouscrit);
            $abonnementSouscrit->setUser($this);
        }

        return $this;
    }

    public function removeAbonnementSouscrit(AbonnementSouscrit $abonnementSouscrit): static
    {
        if ($this->abonnementSouscrits->removeElement($abonnementSouscrit)) {
            // set the owning side to null (unless already changed)
            if ($abonnementSouscrit->getUser() === $this) {
                $abonnementSouscrit->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CarteSouscrite>
     */
    public function getCarteSouscrites(): Collection
    {
        return $this->carteSouscrites;
    }

    public function addCarteSouscrite(CarteSouscrite $carteSouscrite): static
    {
        if (!$this->carteSouscrites->contains($carteSouscrite)) {
            $this->carteSouscrites->add($carteSouscrite);
            $carteSouscrite->setUser($this);
        }

        return $this;
    }

    public function removeCarteSouscrite(CarteSouscrite $carteSouscrite): static
    {
        if ($this->carteSouscrites->removeElement($carteSouscrite)) {
            // set the owning side to null (unless already changed)
            if ($carteSouscrite->getUser() === $this) {
                $carteSouscrite->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SeanceEssai>
     */
    public function getSeanceEssais(): Collection
    {
        return $this->seanceEssais;
    }

    public function addSeanceEssai(SeanceEssai $seanceEssai): static
    {
        if (!$this->seanceEssais->contains($seanceEssai)) {
            $this->seanceEssais->add($seanceEssai);
            $seanceEssai->setUser($this);
        }

        return $this;
    }

    public function removeSeanceEssai(SeanceEssai $seanceEssai): static
    {
        if ($this->seanceEssais->removeElement($seanceEssai)) {
            // set the owning side to null (unless already changed)
            if ($seanceEssai->getUser() === $this) {
                $seanceEssai->setUser(null);
            }
        }

        return $this;
    }
}
