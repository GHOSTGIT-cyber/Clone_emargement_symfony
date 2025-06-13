<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionRepository::class)]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Formation $formation = null;

    #[ORM\ManyToMany(targetEntity: Groupe::class)]
    #[ORM\JoinTable(name: 'session_groupe')]
    private Collection $groupes;

    #[ORM\ManyToOne(targetEntity: Salle::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Salle $salle = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $formateur = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'session', targetEntity: SignatureSession::class, cascade: ['persist'])]
    private Collection $signatures;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'session_apprenant')]
    private Collection $apprenants;

    #[ORM\Column(type: 'boolean')]
    private bool $active = false;

    public function __construct()
    {
        $this->signatures = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->apprenants = new ArrayCollection();
        $this->groupes = new ArrayCollection();
    }

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
    * @return Collection<int, Groupe>
    */
    public function getGroupes(): Collection
    {
        return $this->groupes;
    }

    public function addGroupe(Groupe $groupe): self
    {
        if (!$this->groupes->contains($groupe)) {
        $this->groupes[] = $groupe;
        }

        return $this;
    }

    public function removeGroupe(Groupe $groupe): self
    {
        $this->groupes->removeElement($groupe);
        return $this;
    }

    public function getSalle(): ?Salle
    {
        return $this->salle;
    }

    public function setSalle(?Salle $salle): self
    {
        $this->salle = $salle;
        return $this;
    }

    public function getFormateur(): ?User
    {
        return $this->formateur;
    }

    public function setFormateur(?User $formateur): self
    {
        $this->formateur = $formateur;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;
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

    /**
     * @return Collection<int, SignatureSession>
     */
    public function getSignatures(): Collection
    {
        return $this->signatures;
    }

    public function addSignature(SignatureSession $signature): self
    {
        if (!$this->signatures->contains($signature)) {
            $this->signatures[] = $signature;
            $signature->setSession($this);
        }

        return $this;
    }

    public function removeSignature(SignatureSession $signature): self
    {
        if ($this->signatures->removeElement($signature)) {
            if ($signature->getSession() === $this) {
                $signature->setSession(null);
            }
        }

        return $this;
    }

    public function getApprenants(): Collection
{
    return $this->apprenants;
}

public function addApprenant(User $apprenant): self
{
    if (!$this->apprenants->contains($apprenant)) {
        $this->apprenants[] = $apprenant;

        // Créer automatiquement une signature pour cet apprenant
        $signature = new SignatureSession();
        $signature->setSession($this);
        $signature->setUser($apprenant);
        $signature->setStatut('absent'); // Statut par défaut
        $signature->setJustifie(false);
        
        $this->addSignature($signature);
    }

    return $this;
}

public function removeApprenant(User $apprenant): self
{
    $this->apprenants->removeElement($apprenant);

    return $this;
}

public function isActive(): bool
{
    return $this->active;
}

public function setActive(bool $active): self
{
    $this->active = $active;
    return $this;
}
}