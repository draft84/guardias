<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\GuardAssignment;
use App\Entity\Notification;
use App\Entity\ShiftSwapRequest;
use App\Entity\User;
use App\Repository\GuardAssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api/assignments')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class AssignmentController extends AbstractController
{
    public function __construct(
        private GuardAssignmentRepository $assignmentRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Filtra las asignaciones por departamento del usuario actual
     */
    private function filterByDepartment(array $assignments): array
    {
        $user = $this->getUser();
        
        // ADMIN puede ver todas las asignaciones
        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $assignments;
        }
        
        // Los demás solo ven asignaciones de su departamento
        $userDepartment = $user?->getDepartment();
        
        if (!$userDepartment) {
            return [];
        }
        
        return array_filter($assignments, function (GuardAssignment $assignment) use ($userDepartment) {
            $guard = $assignment->getGuard();
            return $guard && $guard->getDepartment() === $userDepartment;
        });
    }

    #[Route('', name: 'api_assignments_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $assignments = $this->assignmentRepository->findAll();
        $assignments = $this->filterByDepartment($assignments);

        $data = array_map(function (GuardAssignment $assignment) {
            return [
                'id' => (string) $assignment->getId(),
                'guard' => [
                    'id' => (string) $assignment->getGuard()->getId(),
                    'name' => $assignment->getGuard()->getName(),
                    'code' => $assignment->getGuard()->getCode(),
                ],
                'user' => [
                    'id' => (string) $assignment->getUser()->getId(),
                    'fullName' => $assignment->getUser()->getFullName(),
                    'email' => $assignment->getUser()->getEmail(),
                    'guardLevel' => $assignment->getUser()->getGuardLevel()?->getName(),
                    'guardLevelId' => $assignment->getUser()->getGuardLevel()?->getId() ? (string) $assignment->getUser()->getGuardLevel()->getId() : null,
                ],
                'assignedBy' => $assignment->getAssignedBy()?->getFullName(),
                'date' => $assignment->getDate()?->format('Y-m-d'),
                'startTime' => $assignment->getStartTime()?->format('H:i'),
                'endTime' => $assignment->getEndTime()?->format('H:i'),
                'status' => $assignment->getStatus(),
                'notes' => $assignment->getNotes(),
                'createdAt' => $assignment->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $assignments);

        return new JsonResponse(['assignments' => $data], Response::HTTP_OK);
    }

    #[Route('/calendar', name: 'api_assignments_calendar', methods: ['GET'])]
    public function calendar(Request $request): JsonResponse
    {
        $month = (int) $request->query->get('month', date('n'));
        $year = (int) $request->query->get('year', date('Y'));

        // Crear fechas correctamente con hora normalizada
        $startDate = new \DateTime("$year-$month-01 00:00:00");
        $endDate = (clone $startDate)->modify('last day of this month')->setTime(23, 59, 59);

        $allAssignments = [];

        // Obtener todas las asignaciones
        $assignments = $this->assignmentRepository->findAll();
        $assignments = $this->filterByDepartment($assignments);

        foreach ($assignments as $assignment) {
            $assignDate = $assignment->getDate();
            if ($assignDate) {
                // Normalizar fecha para comparación (solo fecha, sin hora)
                $assignDateOnly = clone $assignDate;
                $assignDateOnly->setTime(0, 0, 0);
                $startDateOnly = (clone $startDate)->setTime(0, 0, 0);
                $endDateOnly = (clone $endDate)->setTime(0, 0, 0);

                if ($assignDateOnly >= $startDateOnly && $assignDateOnly <= $endDateOnly) {
                    $allAssignments[] = [
                        'id' => (string) $assignment->getId(),
                        'title' => sprintf(
                            '%s - %s',
                            $assignment->getGuard()->getName(),
                            $assignment->getUser()->getFullName()
                        ),
                        'start' => sprintf(
                            '%s %s',
                            $assignDate->format('Y-m-d'),
                            $assignment->getStartTime()?->format('H:i:s') ?? '00:00:00'
                        ),
                        'end' => sprintf(
                            '%s %s',
                            $assignDate->format('Y-m-d'),
                            $assignment->getEndTime()?->format('H:i:s') ?? '23:59:59'
                        ),
                        'status' => $assignment->getStatus(),
                        'guard' => $assignment->getGuard()->getName(),
                        'user' => $assignment->getUser()->getFullName(),
                        'userId' => (string) $assignment->getUser()->getId(),
                        'guardId' => (string) $assignment->getGuard()->getId(),
                    ];
                }
            }
        }

        return new JsonResponse(['events' => $allAssignments], Response::HTTP_OK);
    }

    #[Route('/date/{date}', name: 'api_assignments_by_date', methods: ['GET'])]
    public function getByDate(\DateTimeInterface $date): JsonResponse
    {
        $assignments = $this->assignmentRepository->findByDate($date);
        
        $data = array_map(function (GuardAssignment $assignment) {
            return [
                'id' => (string) $assignment->getId(),
                'guard' => [
                    'id' => (string) $assignment->getGuard()->getId(),
                    'name' => $assignment->getGuard()->getName(),
                    'code' => $assignment->getGuard()->getCode(),
                ],
                'user' => [
                    'id' => (string) $assignment->getUser()->getId(),
                    'fullName' => $assignment->getUser()->getFullName(),
                    'email' => $assignment->getUser()->getEmail(),
                ],
                'startTime' => $assignment->getStartTime()?->format('H:i'),
                'endTime' => $assignment->getEndTime()?->format('H:i'),
                'status' => $assignment->getStatus(),
            ];
        }, $assignments);

        return new JsonResponse(['assignments' => $data], Response::HTTP_OK);
    }

    #[Route('/user/{userId}', name: 'api_assignments_by_user', methods: ['GET'])]
    public function getByUser(int $userId, Request $request): JsonResponse
    {
        $start = $request->query->get('start', date('Y-m-d'));
        $end = $request->query->get('end', date('Y-m-d', strtotime('+30 days')));
        
        $startDate = new \DateTime($start);
        $endDate = new \DateTime($end);
        
        $assignments = $this->assignmentRepository->findByUserAndDateRange($userId, $startDate, $endDate);

        $data = array_map(function (GuardAssignment $assignment) {
            return [
                'id' => (string) $assignment->getId(),
                'date' => $assignment->getDate()?->format('Y-m-d'),
                'guard' => [
                    'id' => (string) $assignment->getGuard()->getId(),
                    'name' => $assignment->getGuard()->getName(),
                    'code' => $assignment->getGuard()->getCode(),
                ],
                'startTime' => $assignment->getStartTime()?->format('H:i'),
                'endTime' => $assignment->getEndTime()?->format('H:i'),
                'status' => $assignment->getStatus(),
            ];
        }, $assignments);

        return new JsonResponse(['assignments' => $data], Response::HTTP_OK);
    }

    #[Route('/guard/{guardId}/user/{userId}/range', name: 'api_assignments_guard_user_range', methods: ['GET'])]
    public function getByGuardAndUserRange(
        string $guardId,
        string $userId,
        Request $request
    ): JsonResponse {
        // Obtener parámetros de fecha (opcional, usa esta semana por defecto)
        $startDate = $request->query->get('start');
        $endDate = $request->query->get('end');

        error_log('📍 RANGE REQUEST - Guard ID: ' . $guardId);
        error_log('📍 RANGE REQUEST - User ID: ' . $userId);
        error_log('📍 RANGE REQUEST - Start: ' . ($startDate ?? 'null'));
        error_log('📍 RANGE REQUEST - End: ' . ($endDate ?? 'null'));

        // Si no se proporcionan fechas, usar la semana actual
        if (!$startDate || !$endDate) {
            $now = new \DateTime();
            $startDate = clone $now;
            $startDate->modify('monday this week');
            $startDate->setTime(0, 0, 0);
            
            $endDate = clone $now;
            $endDate->modify('sunday this week');
            $endDate->setTime(23, 59, 59);
        } else {
            $startDate = new \DateTime($startDate);
            $endDate = new \DateTime($endDate);
        }

        error_log('📍 RANGE REQUEST - Date range: ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'));

        $assignments = $this->assignmentRepository->findByGuardAndUserAndDateRange(
            $guardId,
            $userId,
            $startDate,
            $endDate
        );

        error_log('📍 RANGE REQUEST - Found assignments: ' . count($assignments));

        $data = array_map(function (GuardAssignment $assignment) {
            return [
                'id' => (string) $assignment->getId(),
                'date' => $assignment->getDate()?->format('Y-m-d'),
                'guard' => [
                    'id' => (string) $assignment->getGuard()->getId(),
                    'name' => $assignment->getGuard()->getName(),
                ],
                'user' => [
                    'id' => (string) $assignment->getUser()->getId(),
                    'fullName' => $assignment->getUser()->getFullName(),
                ],
                'startTime' => $assignment->getStartTime()?->format('H:i'),
                'endTime' => $assignment->getEndTime()?->format('H:i'),
                'status' => $assignment->getStatus(),
            ];
        }, $assignments);

        return new JsonResponse(['assignments' => $data], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_assignments_get', methods: ['GET'])]
    public function get(GuardAssignment $assignment): JsonResponse
    {
        $user = $assignment->getUser();
        $guard = $assignment->getGuard();
        $data = [
            'id' => (string) $assignment->getId(),
            'guard' => [
                'id' => (string) $guard->getId(),
                'name' => $guard->getName(),
                'code' => $guard->getCode(),
                'departmentName' => $guard->getDepartment()?->getName() ?? null,
            ],
            'user' => [
                'id' => (string) $user->getId(),
                'fullName' => $user->getFullName(),
                'email' => $user->getEmail(),
                'phone' => $user->getPhone() ? '+' . $user->getPhone()->getCountryCode() . ' ' . $user->getPhone()->getNationalNumber() : null,
                'guardLevel' => $user->getGuardLevel()?->getName() ?? null,
                'guardLevelId' => $user->getGuardLevel()?->getId() ? (string) $user->getGuardLevel()->getId() : null,
            ],
            'assignedBy' => $assignment->getAssignedBy()?->getFullName(),
            'date' => $assignment->getDate()?->format('Y-m-d'),
            'startTime' => $assignment->getStartTime()?->format('H:i'),
            'endTime' => $assignment->getEndTime()?->format('H:i'),
            'status' => $assignment->getStatus(),
            'notes' => $assignment->getNotes(),
        ];

        return new JsonResponse(['assignment' => $data], Response::HTTP_OK);
    }

    #[Route('', name: 'api_assignments_create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] UserInterface $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['guardId']) || empty($data['userId']) || empty($data['date'])) {
            return new JsonResponse([
                'error' => 'Guard ID, User ID and date are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $guard = $this->entityManager->getRepository(\App\Entity\Guard::class)->find($data['guardId']);
        $assignedUser = $this->entityManager->getRepository(\App\Entity\User::class)->find($data['userId']);

        if (!$guard || !$assignedUser) {
            return new JsonResponse([
                'error' => 'Guard or User not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $assignment = new GuardAssignment();
        $assignment->setGuard($guard);
        $assignment->setUser($assignedUser);
        $assignment->setAssignedBy($user);
        $assignment->setDate(new \DateTime($data['date']));
        
        // Crear DateTime para startTime y endTime con formato H:i
        $startTime = \DateTime::createFromFormat('H:i', $data['startTime'] ?? $guard->getStartTime()->format('H:i'));
        $endTime = \DateTime::createFromFormat('H:i', $data['endTime'] ?? $guard->getEndTime()->format('H:i'));
        
        if ($startTime) {
            $assignment->setStartTime($startTime);
        }
        if ($endTime) {
            $assignment->setEndTime($endTime);
        }
        
        $assignment->setStatus($data['status'] ?? 'scheduled');
        
        if (isset($data['notes']) && !empty($data['notes'])) {
            $assignment->setNotes($data['notes']);
        }

        $this->entityManager->persist($assignment);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Assignment created successfully',
            'assignment' => [
                'id' => (string) $assignment->getId(),
                'guard' => [
                    'id' => (string) $guard->getId(),
                    'name' => $guard->getName(),
                ],
                'user' => [
                    'id' => (string) $assignedUser->getId(),
                    'fullName' => $assignedUser->getFullName(),
                ],
                'date' => $assignment->getDate()->format('Y-m-d'),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_assignments_update', methods: ['PUT'])]
    public function update(Request $request, GuardAssignment $assignment): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['status'])) {
            $assignment->setStatus($data['status']);
        }
        if (isset($data['notes'])) {
            $assignment->setNotes($data['notes']);
        }
        if (isset($data['startTime'])) {
            $assignment->setStartTime(new \DateTime($data['startTime']));
        }
        if (isset($data['endTime'])) {
            $assignment->setEndTime(new \DateTime($data['endTime']));
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Assignment updated successfully',
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_assignments_delete', methods: ['DELETE'])]
    public function delete(GuardAssignment $assignment): JsonResponse
    {
        $this->entityManager->remove($assignment);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Assignment deleted successfully',
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/swap', name: 'api_assignments_request_swap', methods: ['POST'])]
    public function requestSwap(
        Request $request,
        GuardAssignment $assignment,
        #[CurrentUser] UserInterface $user
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data['newUserId'])) {
            return new JsonResponse([
                'error' => 'New user ID is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar que el usuario que solicita el cambio es el usuario asignado
        $currentUserId = method_exists($user, 'getId') ? (string) $user->getId() : null;
        $assignedUserId = (string) $assignment->getUser()->getId();

        if ($currentUserId !== $assignedUserId) {
            return new JsonResponse([
                'error' => 'Only the assigned user can request a swap',
            ], Response::HTTP_FORBIDDEN);
        }

        // Buscar el nuevo usuario
        $newUser = $this->entityManager->getRepository(User::class)->find($data['newUserId']);
        if (!$newUser) {
            return new JsonResponse([
                'error' => 'New user not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Obtener fechas a cambiar (puede ser una o varias)
        $dates = $data['dates'] ?? [$assignment->getDate()->format('Y-m-d')];
        $swapType = $data['swapType'] ?? 'single';

        error_log('🔄 SWAP REQUEST - Type: ' . $swapType);
        error_log('🔄 SWAP REQUEST - Dates: ' . implode(', ', $dates));
        error_log('🔄 SWAP REQUEST - Requested by: ' . $user->getFullName() . ' (' . $user->getEmail() . ')');
        error_log('🔄 SWAP REQUEST - New user: ' . $newUser->getFullName() . ' (' . $newUser->getEmail() . ')');

        // Buscar todas las asignaciones para las fechas especificadas
        $assignments = [];
        foreach ($dates as $date) {
            $assignDate = \DateTime::createFromFormat('Y-m-d', $date);
            if ($assignDate) {
                $repoAssignment = $this->entityManager->getRepository(GuardAssignment::class)->findOneBy([
                    'guard' => $assignment->getGuard(),
                    'user' => $assignment->getUser(),
                    'date' => $assignDate,
                ]);
                if ($repoAssignment) {
                    $assignments[] = $repoAssignment;
                }
            }
        }

        if (empty($assignments)) {
            return new JsonResponse([
                'error' => 'No assignments found for the specified dates',
            ], Response::HTTP_NOT_FOUND);
        }

        // Crear UNA sola notificación con el resumen de todos los días
        $assignmentIds = array_map(fn($a) => (string) $a->getId(), $assignments);
        $datesList = array_map(fn($a) => $a->getDate()->format('d/m/Y'), $assignments);
        $count = count($assignments);

        // Construir mensaje según la cantidad de días
        if ($count === 1) {
            $message = sprintf(
                '%s solicita intercambiar su guardia del %s (%s - %s). ¿Aceptas el cambio?',
                $user->getFullName(),
                $assignments[0]->getDate()->format('d/m/Y'),
                $assignments[0]->getStartTime()->format('H:i'),
                $assignments[0]->getEndTime()->format('H:i')
            );
        } else {
            $message = sprintf(
                '%s solicita intercambiar %d guardias. ¿Aceptas el cambio? Días: %s',
                $user->getFullName(),
                $count,
                implode(', ', $datesList)
            );
        }

        $notification = new Notification();
        $notification->setUser($newUser);
        $notification->setType('swap_request');
        $notification->setTitle('Solicitud de Cambio de Guardia' . ($count > 1 ? ' (' . $count . ' días)' : ''));
        $notification->setMessage($message);
        $notification->setData([
            'type' => 'swap_request',
            'assignmentIds' => $assignmentIds, // Array con TODOS los IDs
            'assignmentId' => $assignmentIds[0], // Mantener compatibilidad
            'requestedBy' => [
                'id' => (string) $user->getId(),
                'fullName' => $user->getFullName(),
            ],
            'guard' => [
                'name' => $assignment->getGuard()->getName(),
                'dates' => $datesList, // Array con todas las fechas
                'count' => $count,
            ],
            'reason' => $data['reason'] ?? null,
            'swapType' => $swapType,
            'totalAssignments' => $count,
        ]);

        $this->entityManager->persist($notification);
        
        // Notificar a los managers del departamento del usuario que solicita el cambio
        $userDepartment = $user->getDepartment();
        if ($userDepartment) {
            // Obtener todos los usuarios del departamento
            $departmentUsers = $this->entityManager->getRepository(User::class)->findBy([
                'department' => $userDepartment,
            ]);
            
            foreach ($departmentUsers as $manager) {
                $roles = $manager->getRoles();
                // Verificar si es SOLO MANAGER (no ADMIN)
                if (in_array('ROLE_MANAGER', $roles, true) && !in_array('ROLE_ADMIN', $roles, true)) {
                    // No notificar al mismo usuario que solicita el cambio ni al destinatario
                    if ($manager->getId() !== $user->getId() && $manager->getId() !== $newUser->getId()) {
                        $managerNotification = new Notification();
                        $managerNotification->setUser($manager);
                        $managerNotification->setType('swap_request');
                        $managerNotification->setTitle('Solicitud de Cambio de Guardia (Información)');
                        $managerNotification->setMessage(
                            sprintf(
                                '%s solicitó cambio de %d guardia(s) a %s. Días: %s',
                                $user->getFullName(),
                                $count,
                                $newUser->getFullName(),
                                implode(', ', $datesList)
                            )
                        );
                        $managerNotification->setData([
                            'type' => 'swap_request_info',
                            'requestedBy' => [
                                'id' => (string) $user->getId(),
                                'fullName' => $user->getFullName(),
                            ],
                            'assignedTo' => [
                                'id' => (string) $newUser->getId(),
                                'fullName' => $newUser->getFullName(),
                            ],
                            'guard' => [
                                'name' => $assignment->getGuard()->getName(),
                                'dates' => $datesList,
                                'count' => $count,
                            ],
                        ]);
                        $this->entityManager->persist($managerNotification);
                        error_log('📬 MANAGER NOTIFICATION - Sent to: ' . $manager->getEmail() . ' (Roles: ' . implode(', ', $roles) . ')');
                    }
                }
            }
        }
        
        $this->entityManager->flush();

        error_log('🔔 SWAP NOTIFICATION - Created with ID: ' . $notification->getId());
        error_log('🔔 SWAP NOTIFICATION - Assignment IDs: ' . implode(', ', $assignmentIds));

        $messageResponse = $count > 1 
            ? sprintf('Solicitud de cambio enviada para %d días. El usuario debe aceptar para completar el cambio.', $count)
            : 'Solicitud de cambio enviada. El usuario debe aceptar para completar el cambio.';

        return new JsonResponse([
            'message' => $messageResponse,
            'notificationId' => (string) $notification->getId(),
            'count' => $count,
        ], Response::HTTP_OK);
    }

    #[Route('/swap/{swapId}/approve', name: 'api_assignments_approve_swap', methods: ['PUT'])]
    public function approveSwap(string $swapId): JsonResponse
    {
        // Lógica para aprobar cambio
        return new JsonResponse([
            'message' => 'Swap approved successfully',
        ], Response::HTTP_OK);
    }

    #[Route('/swap/{swapId}/reject', name: 'api_assignments_reject_swap', methods: ['PUT'])]
    public function rejectSwap(string $swapId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Lógica para rechazar cambio
        return new JsonResponse([
            'message' => 'Swap rejected',
        ], Response::HTTP_OK);
    }
}
