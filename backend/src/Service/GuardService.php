<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Guard;
use App\Entity\GuardAssignment;
use App\Repository\GuardRepository;
use App\Repository\GuardAssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;

class GuardService
{
    public function __construct(
        private GuardRepository $guardRepository,
        private GuardAssignmentRepository $assignmentRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @return Guard[]
     */
    public function getAllGuards(): array
    {
        return $this->guardRepository->findAll();
    }

    /**
     * @return Guard[]
     */
    public function getActiveGuards(): array
    {
        return $this->guardRepository->findActiveGuards();
    }

    public function getGuardById(string $id): ?Guard
    {
        return $this->guardRepository->find($id);
    }

    /**
     * @return Guard[]
     */
    public function getGuardsByDepartment(string $departmentId): array
    {
        return $this->guardRepository->findByDepartment($departmentId);
    }

    public function createGuard(
        string $name,
        string $code,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?string $description = null,
        ?string $departmentId = null,
        bool $active = true
    ): Guard {
        $guard = new Guard();
        $guard->setName($name);
        $guard->setCode($code);
        $guard->setStartTime($startTime);
        $guard->setEndTime($endTime);
        $guard->setDescription($description);
        $guard->setActive($active);

        $this->entityManager->persist($guard);
        $this->entityManager->flush();

        return $guard;
    }

    public function updateGuard(
        Guard $guard,
        ?string $name = null,
        ?string $code = null,
        ?\DateTimeInterface $startTime = null,
        ?\DateTimeInterface $endTime = null,
        ?string $description = null,
        ?bool $active = null
    ): Guard {
        if ($name !== null) {
            $guard->setName($name);
        }
        if ($code !== null) {
            $guard->setCode($code);
        }
        if ($startTime !== null) {
            $guard->setStartTime($startTime);
        }
        if ($endTime !== null) {
            $guard->setEndTime($endTime);
        }
        if ($description !== null) {
            $guard->setDescription($description);
        }
        if ($active !== null) {
            $guard->setActive($active);
        }

        $this->entityManager->flush();

        return $guard;
    }

    public function deleteGuard(Guard $guard): void
    {
        $this->entityManager->remove($guard);
        $this->entityManager->flush();
    }

    public function assignGuard(
        Guard $guard,
        $user,
        $assignedBy,
        \DateTimeInterface $date,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?string $notes = null
    ): GuardAssignment {
        $assignment = new GuardAssignment();
        $assignment->setGuard($guard);
        $assignment->setUser($user);
        $assignment->setAssignedBy($assignedBy);
        $assignment->setDate($date);
        $assignment->setStartTime($startTime);
        $assignment->setEndTime($endTime);
        $assignment->setStatus('scheduled');
        $assignment->setNotes($notes);

        $this->entityManager->persist($assignment);
        $this->entityManager->flush();

        return $assignment;
    }

    public function unassignGuard(GuardAssignment $assignment): void
    {
        $this->entityManager->remove($assignment);
        $this->entityManager->flush();
    }

    public function updateAssignmentStatus(GuardAssignment $assignment, string $status): GuardAssignment
    {
        $validStatuses = ['scheduled', 'active', 'completed', 'cancelled', 'swapped'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: $status");
        }

        $assignment->setStatus($status);
        $this->entityManager->flush();

        return $assignment;
    }

    /**
     * @return GuardAssignment[]
     */
    public function getAssignmentsForGuard(Guard $guard): array
    {
        return $guard->getAssignments()->toArray();
    }

    public function hasOverlappingAssignment(
        $user,
        \DateTimeInterface $date,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        ?GuardAssignment $excludeAssignment = null
    ): bool {
        $assignments = $this->assignmentRepository->findByDate($date);
        
        foreach ($assignments as $assignment) {
            if ($assignment->getUser() !== $user) {
                continue;
            }
            
            if ($excludeAssignment !== null && $assignment === $excludeAssignment) {
                continue;
            }

            $assignStart = $assignment->getStartTime();
            $assignEnd = $assignment->getEndTime();

            // Check for overlap
            if ($startTime < $assignEnd && $endTime > $assignStart) {
                return true;
            }
        }

        return false;
    }
}
