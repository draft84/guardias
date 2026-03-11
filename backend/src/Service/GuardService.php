<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Guard;
use App\Entity\GuardAssignment;
use App\Entity\User;
use App\Repository\GuardRepository;
use App\Repository\GuardAssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class GuardService
{
    public function __construct(
        private GuardRepository $guardRepository,
        private GuardAssignmentRepository $assignmentRepository,
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    /**
     * @return Guard[]
     */
    public function getAllGuards(): array
    {
        $user = $this->security->getUser();
        
        // Si es ADMIN, devuelve todas las guardias
        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->guardRepository->findAll();
        }
        
        // Si no, solo las del departamento del usuario
        $department = $user?->getDepartment();
        if (!$department) {
            return [];
        }
        
        return $this->guardRepository->findBy(['department' => $department]);
    }

    /**
     * @return Guard[]
     */
    public function getActiveGuards(): array
    {
        $user = $this->security->getUser();
        
        // Si es ADMIN, devuelve todas las guardias activas
        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->guardRepository->findActiveGuards();
        }
        
        // Si no, solo las del departamento del usuario
        $department = $user?->getDepartment();
        if (!$department) {
            return [];
        }
        
        return $this->guardRepository->findBy([
            'department' => $department,
            'active' => true
        ]);
    }

    public function getGuardById(string $id): ?Guard
    {
        $guard = $this->guardRepository->find($id);
        
        if (!$guard) {
            return null;
        }
        
        // Verificar permisos por departamento
        $user = $this->security->getUser();
        
        // ADMIN puede ver todas
        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $guard;
        }
        
        // Los demás solo pueden ver las de su departamento
        $userDepartment = $user?->getDepartment();
        $guardDepartment = $guard->getDepartment();
        
        if (!$userDepartment || !$guardDepartment) {
            return null;
        }
        
        if ($userDepartment !== $guardDepartment) {
            return null;
        }
        
        return $guard;
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
        // Obtener todas las asignaciones de esta guardia
        $assignments = $guard->getAssignments();
        $assignmentIds = [];
        
        // Recopilar todos los IDs de asignaciones
        foreach ($assignments as $assignment) {
            $assignmentIds[] = (string) $assignment->getId();
        }
        
        error_log('🗑️ DELETE GUARD: ' . $guard->getName());
        error_log('🗑️ Assignment IDs to remove: ' . implode(', ', $assignmentIds));
        
        // Buscar TODAS las notificaciones de tipo swap_request
        $allNotifications = $this->entityManager->getRepository(\App\Entity\Notification::class)->findAll();
        error_log('🗑️ Total notifications found: ' . count($allNotifications));
        
        $deletedCount = 0;
        // Eliminar notificaciones que contengan cualquiera de las asignaciones eliminadas
        foreach ($allNotifications as $notification) {
            $data = $notification->getData();
            if ($data) {
                $notificationAssignmentIds = $data['assignmentIds'] ?? [$data['assignmentId'] ?? null];
                $notificationAssignmentIds = array_filter($notificationAssignmentIds); // Eliminar nulls
                
                // Verificar si hay intersección entre los IDs
                $intersection = array_intersect($notificationAssignmentIds, $assignmentIds);
                
                if (!empty($intersection)) {
                    // Esta notificación tiene al menos una asignación que se está eliminando
                    $this->entityManager->remove($notification);
                    $deletedCount++;
                    error_log('🗑️ NOTIFICATION DELETED: ' . $notification->getId() . ' - Assignments matched: ' . implode(', ', $intersection));
                }
            }
        }
        
        error_log('🗑️ Notifications deleted: ' . $deletedCount);
        
        // Eliminar las asignaciones
        foreach ($assignments as $assignment) {
            $this->entityManager->remove($assignment);
        }
        
        // Eliminar la guardia
        $this->entityManager->remove($guard);
        $this->entityManager->flush();
        
        error_log('🗑️ GUARD DELETED: ' . $guard->getName() . ' - Removed ' . count($assignments) . ' assignments');
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
