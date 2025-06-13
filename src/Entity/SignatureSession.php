<?php

namespace App\Entity;

use App\Enum\SessionStatut;
use App\Enum\MotifAbsence;
use App\Repository\SignatureSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SignatureSessionRepository::class)]
class SignatureSession

{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Session::class, inversedBy: 'signatures')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Session $session = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $statut = null; // Ex: "présent", "absent", "retard"

    #[ORM\Column(type: 'boolean')]
    private bool $justifie = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $motifAbsence = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motifDetails = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $heureSignature = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $document = null; 

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $signatureData = null;

   
    // Getters / Setters...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(?Session $session): self
    {
        $this->session = $session;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function isJustifie(): bool
    {
        return $this->justifie;
    }

    public function setJustifie(bool $justifie): self
    {
        $this->justifie = $justifie;
        return $this;
    }

    public function getMotifAbsence(): ?string
    {
        return $this->motifAbsence;
    }

    public function setMotifAbsence(?string $motifAbsence): self
    {
        $this->motifAbsence = $motifAbsence;
        return $this;
    }

    public function getMotifDetails(): ?string
    {
        return $this->motifDetails;
    }

    public function setMotifDetails(?string $motifDetails): self
    {
        $this->motifDetails = $motifDetails;
        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): self
    {
        $this->commentaire = $commentaire;
        return $this;
    }

    public function getHeureSignature(): ?\DateTimeInterface
    {
        return $this->heureSignature;
    }

    public function setHeureSignature(?\DateTimeInterface $heureSignature): self
    {
        $this->heureSignature = $heureSignature;
        return $this;
    }

    public function getSignatureData(): ?string
    {
        return $this->signatureData;
    }

    public function setSignatureData(?string $signatureData): self
    {
        $this->signatureData = $signatureData;
        return $this;
    }

    public function getDocument(): ?string
    {
        return $this->document;
    }

    public function setDocument(?string $document): self
    {
        $this->document = $document;
        return $this;
    }
}