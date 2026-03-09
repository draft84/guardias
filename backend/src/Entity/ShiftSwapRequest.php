<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ShiftSwapRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ShiftSwapRequestRepository::class)]
#[ORM\Table(name: 'shift_swap_requests')]
#[ORM\HasLifecycleCallbacks]
class ShiftSwapRequest
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\OneToOne(inversedBy: 'swapRequest', targetEntity: GuardAssignment::class)]
    #[ORM\JoinColumn(name: 'original_assignment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?GuardAssignment $originalAssignment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'new_user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $newUser = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'swapRequests')]
    #[ORM\JoinColumn(name: 'requested_by', referencedColumnName: 'id', nullable: false)]
    private ?User $requestedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $requestedAt = null;

    #[ORM\Column(length: 20, options: ['default' => 'pending'])]
    private ?string $status = 'pending';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'approved_by', referencedColumnName: 'id', nullable: true)]
    private ?User $approvedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $approvedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    public function __construct()
    {
        $this->requestedAt = new \DateTime();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getOriginalAssignment(): ?GuardAssignment
    {
        return $this->originalAssignment;
    }

    public function setOriginalAssignment(?GuardAssignment $originalAssignment): static
    {
        $this->originalAssignment = $originalAssignment;
        return $this;
    }

    public function getNewUser(): ?User
    {
        return $this->newUser;
    }

    public function setNewUser(?User $newUser): static
    {
        $this->newUser = $newUser;
        return $this;
    }

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?User $requestedBy): static
    {
        $this->requestedBy = $requestedBy;
        return $this;
    }

    public function getRequestedAt(): ?\DateTimeInterface
    {
        return $this->requestedAt;
    }

    public function setRequestedAt(\DateTimeInterface $requestedAt): static
    {
        $this->requestedAt = $requestedAt;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): static
    {
        $this->approvedBy = $approvedBy;
        return $this;
    }

    public function getApprovedAt(): ?\DateTimeInterface
    {
        return $this->approvedAt;
    }

    public function setApprovedAt(?\DateTimeInterface $approvedAt): static
    {
        $this->approvedAt = $approvedAt;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): static
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        if ($this->approvedAt === null && in_array($this->status, ['approved', 'rejected'])) {
            $this->approvedAt = new \DateTime();
        }
    }
}
