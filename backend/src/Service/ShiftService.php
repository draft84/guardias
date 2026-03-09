<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Shift;
use App\Entity\ShiftSwapRequest;
use App\Entity\GuardAssignment;
use App\Repository\ShiftRepository;
use App\Repository\ShiftSwapRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

class ShiftService
{
    public function __construct(
        private ShiftRepository $shiftRepository,
        private ShiftSwapRequestRepository $swapRequestRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @return Shift[]
     */
    public function getAllShifts(): array
    {
        return $this->shiftRepository->findAll();
    }

    /**
     * @return Shift[]
     */
    public function getActiveShifts(): array
    {
        return $this->shiftRepository->findActiveShifts();
    }

    public function getShiftById(string $id): ?Shift
    {
        return $this->shiftRepository->find($id);
    }

    public function createShift(
        string $name,
        string $code,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        string $type = 'custom',
        string $color = '#3498db',
        bool $active = true
    ): Shift {
        $shift = new Shift();
        $shift->setName($name);
        $shift->setCode($code);
        $shift->setStartTime($startTime);
        $shift->setEndTime($endTime);
        $shift->setType($type);
        $shift->setColor($color);
        $shift->setActive($active);

        $this->entityManager->persist($shift);
        $this->entityManager->flush();

        return $shift;
    }

    public function updateShift(
        Shift $shift,
        ?string $name = null,
        ?string $code = null,
        ?\DateTimeInterface $startTime = null,
        ?\DateTimeInterface $endTime = null,
        ?string $type = null,
        ?string $color = null,
        ?bool $active = null
    ): Shift {
        if ($name !== null) {
            $shift->setName($name);
        }
        if ($code !== null) {
            $shift->setCode($code);
        }
        if ($startTime !== null) {
            $shift->setStartTime($startTime);
        }
        if ($endTime !== null) {
            $shift->setEndTime($endTime);
        }
        if ($type !== null) {
            $shift->setType($type);
        }
        if ($color !== null) {
            $shift->setColor($color);
        }
        if ($active !== null) {
            $shift->setActive($active);
        }

        $this->entityManager->flush();

        return $shift;
    }

    public function deleteShift(Shift $shift): void
    {
        $this->entityManager->remove($shift);
        $this->entityManager->flush();
    }

    public function requestSwap(
        GuardAssignment $originalAssignment,
        $newUser,
        $requestedBy,
        ?string $reason = null
    ): ShiftSwapRequest {
        $swapRequest = new ShiftSwapRequest();
        $swapRequest->setOriginalAssignment($originalAssignment);
        $swapRequest->setNewUser($newUser);
        $swapRequest->setRequestedBy($requestedBy);
        $swapRequest->setReason($reason);
        $swapRequest->setStatus('pending');

        $originalAssignment->setSwapRequest($swapRequest);

        $this->entityManager->persist($swapRequest);
        $this->entityManager->flush();

        return $swapRequest;
    }

    public function approveSwap(ShiftSwapRequest $swapRequest, $approvedBy): ShiftSwapRequest
    {
        $swapRequest->setStatus('approved');
        $swapRequest->setApprovedBy($approvedBy);
        $swapRequest->setApprovedAt(new \DateTime());

        // Actualizar la asignación original con el nuevo usuario
        $assignment = $swapRequest->getOriginalAssignment();
        if ($assignment !== null) {
            $assignment->setUser($swapRequest->getNewUser());
            $assignment->setStatus('swapped');
            $assignment->setSwappedAt(new \DateTime());
        }

        $this->entityManager->flush();

        return $swapRequest;
    }

    public function rejectSwap(ShiftSwapRequest $swapRequest, $approvedBy, ?string $reason = null): ShiftSwapRequest
    {
        $swapRequest->setStatus('rejected');
        $swapRequest->setApprovedBy($approvedBy);
        $swapRequest->setApprovedAt(new \DateTime());
        $swapRequest->setRejectionReason($reason);

        $this->entityManager->flush();

        return $swapRequest;
    }

    /**
     * @return ShiftSwapRequest[]
     */
    public function getPendingSwapRequests(): array
    {
        return $this->swapRequestRepository->findPendingRequests();
    }

    /**
     * @return ShiftSwapRequest[]
     */
    public function getSwapRequestsByUser($user): array
    {
        $userId = is_object($user) && method_exists($user, 'getId') ? $user->getId() : $user;
        return $this->swapRequestRepository->findByUser($userId);
    }

    public function getSwapRequestById(string $id): ?ShiftSwapRequest
    {
        return $this->swapRequestRepository->find($id);
    }
}
