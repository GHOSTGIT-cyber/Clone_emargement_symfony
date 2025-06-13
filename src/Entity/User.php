<?php

namespace App\Entity;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 50)]
    private ?string $firstname = null;

    #[ORM\Column(length: 50)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', enumType: UserRole::class)]
    private ?UserRole $role = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PasswordReset::class, orphanRemoval: true)]
    private Collection $passwordResets;

    #[ORM\ManyToMany(targetEntity: Session::class, mappedBy: 'apprenants')]
    private Collection $sessions;

    #[ORM\ManyToMany(targetEntity: Groupe::class, mappedBy: 'apprenants')]
    private Collection $groupes;

    public function __construct()
    {
        $this->passwordResets = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();

        $this->sessions = new ArrayCollection();
        $this->groupes = new ArrayCollection();

    }

    // ===========================
    // Getters / Setters
    // ===========================

    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRole(): ?UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getSessions(): Collection
{
    return $this->sessions;
}

public function getGroupes(): Collection
{
    return $this->groupes;
}

    /**
     * @return Collection<int, PasswordReset>
     */
    public function getPasswordResets(): Collection
    {
        return $this->passwordResets;
    }

    public function addPasswordReset(PasswordReset $passwordReset): self
    {
        if (!$this->passwordResets->contains($passwordReset)) {
            $this->passwordResets[] = $passwordReset;
            $passwordReset->setUser($this);
        }

        return $this;
    }

    public function removePasswordReset(PasswordReset $passwordReset): self
    {
        if ($this->passwordResets->removeElement($passwordReset)) {
            if ($passwordReset->getUser() === $this) {
                $passwordReset->setUser(null);
            }
        }

        return $this;
    }

    // ===========================
    // Méthodes imposées par UserInterface
    // ===========================

    /**
     * Retourne l'identifiant unique de l'utilisateur (utilisé par Symfony).
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * Retourne les rôles Symfony de l'utilisateur.
     */
    public function getRoles(): array
    {
        return ['ROLE_' . strtoupper($this->role->value)];
    }

    /**
     * Permet d'effacer les credentials sensibles en cas de besoin.
     */
    public function eraseCredentials(): void
    {
        // Si tu stockes des infos sensibles temporaires, c'est ici que tu les effaces.
        // Ex : $this->plainPassword = null;
    }

    public function __toString(): string
{
    return $this->firstname . ' ' . $this->lastname;
}
}