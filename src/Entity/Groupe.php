<?php

namespace App\Entity;

use App\Repository\GroupeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupeRepository::class)]
class Groupe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'groupes')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Formation $formation = null;

   #[ORM\ManyToMany(targetEntity: Session::class, mappedBy: 'groupes')]
    private Collection $sessions;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'groupes_apprenants')]
    private Collection $apprenants;

    public function __construct()
    {
        $this->apprenants = new ArrayCollection();
        $this->sessions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    // ===========================
    // Getters / Setters
    // ===========================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
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

    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    public function setFormation(?Formation $formation): self
    {
        $this->formation = $formation;
        return $this;
    }

    /**
     * @return Collection<int, Session>
     */

    public function getSessions(): Collection
{
    return $this->sessions;
}

public function addSession(Session $session): self
{
    if (!$this->sessions->contains($session)) {
        $this->sessions[] = $session;
        $session->addGroupe($this); // synchronisation bidirectionnelle
    }

    return $this;
}

public function removeSession(Session $session): self
{
    if ($this->sessions->removeElement($session)) {
        $session->removeGroupe($this); // synchronisation bidirectionnelle
    }

    return $this;
}

    /**
     * @return Collection<int, User>
     */
    public function getApprenants(): Collection
    {
        return $this->apprenants;
    }

    public function addApprenant(User $apprenant): self
    {
        if (!$this->apprenants->contains($apprenant)) {
            $this->apprenants[] = $apprenant;
        }

        return $this;
    }

    public function removeApprenant(User $apprenant): self
    {
        $this->apprenants->removeElement($apprenant);

        return $this;
    }
}