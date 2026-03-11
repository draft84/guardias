<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\GuardAssignment;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api/notifications')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'api_notifications_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        error_log('🔔 NOTIFICATIONS LIST - User email: ' . $user->getEmail());
        error_log('🔔 NOTIFICATIONS LIST - User ID: ' . $user->getId());
        
        $limit = (int) $request->query->get('limit', 20);
        $unreadOnly = $request->query->getBoolean('unread', false);

        $notifications = $this->notificationRepository->findByUser(
            $user,
            $unreadOnly,
            $limit
        );

        error_log('🔔 NOTIFICATIONS LIST - Count: ' . count($notifications));

        $data = array_map(function (Notification $notification) {
            return [
                'id' => (string) $notification->getId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'data' => $notification->getData(),
                'read' => $notification->isRead(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
                'readAt' => $notification->getReadAt()?->format('Y-m-d H:i:s'),
            ];
        }, $notifications);

        return new JsonResponse([
            'notifications' => $data,
            'unreadCount' => $this->notificationRepository->countUnread($user)
        ], Response::HTTP_OK);
    }

    #[Route('/count', name: 'api_notifications_count', methods: ['GET'])]
    public function count(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        error_log('🔔 NOTIFICATIONS COUNT - User email: ' . $user->getEmail());
        error_log('🔔 NOTIFICATIONS COUNT - User ID: ' . $user->getId());
        
        $unreadCount = $this->notificationRepository->countUnread($user);
        
        error_log('🔔 NOTIFICATIONS COUNT - Unread: ' . $unreadCount);

        return new JsonResponse([
            'unreadCount' => $unreadCount
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/read', name: 'api_notifications_mark_read', methods: ['PUT'])]
    public function markAsRead(Notification $notification): JsonResponse
    {
        $user = $this->getUser();

        // Solo el usuario propietario puede marcar como leída
        if ($notification->getUser()->getId() !== $user->getId()) {
            return new JsonResponse([
                'error' => 'Unauthorized',
            ], Response::HTTP_FORBIDDEN);
        }

        $notification->setRead(true);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Notification marked as read',
        ], Response::HTTP_OK);
    }

    #[Route('/read-all', name: 'api_notifications_mark_all_read', methods: ['PUT'])]
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->getUser();
        $notifications = $user->getNotifications();

        foreach ($notifications as $notification) {
            if (!$notification->isRead()) {
                $notification->setRead(true);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'All notifications marked as read',
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/accept-swap', name: 'api_notifications_accept_swap', methods: ['POST'])]
    public function acceptSwap(
        Notification $notification,
        #[CurrentUser] UserInterface $user
    ): JsonResponse {
        // Verificar que la notificación es para este usuario
        if ($notification->getUser()->getId() !== $user->getId()) {
            return new JsonResponse([
                'error' => 'Unauthorized',
            ], Response::HTTP_FORBIDDEN);
        }

        // Verificar que es una notificación de swap
        $data = $notification->getData();
        if (!$data || $data['type'] !== 'swap_request') {
            return new JsonResponse([
                'error' => 'Invalid notification type',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Obtener IDs de asignaciones (puede ser una o varias)
        $assignmentIds = $data['assignmentIds'] ?? [$data['assignmentId']];
        $count = count($assignmentIds);

        // Actualizar TODAS las asignaciones
        $updatedAssignments = [];
        foreach ($assignmentIds as $assignmentId) {
            $assignment = $this->entityManager->getRepository(GuardAssignment::class)->find($assignmentId);
            if (!$assignment) {
                return new JsonResponse([
                    'error' => 'Assignment not found: ' . $assignmentId,
                ], Response::HTTP_NOT_FOUND);
            }

            // CAMBIO INMEDIATO - Actualizar la asignación con el nuevo usuario
            $assignment->setUser($user);

            // Agregar nota del cambio
            $existingNotes = $assignment->getNotes() ?? '';
            $assignment->setNotes(
                $existingNotes . "\n[Cambio Aceptado: " .
                $data['requestedBy']['fullName'] . ' → ' . $user->getFullName() .
                ' - ' . date('Y-m-d H:i') . ']'
            );

            $updatedAssignments[] = [
                'id' => (string) $assignment->getId(),
                'date' => $assignment->getDate()->format('Y-m-d'),
            ];
        }

        // Marcar notificación como leída
        $notification->setRead(true);

        // Crear notificación para el usuario que solicitó el cambio
        $requestingUser = $this->entityManager->getRepository(User::class)->find($data['requestedBy']['id']);
        if ($requestingUser) {
            $confirmationNotification = new \App\Entity\Notification();
            $confirmationNotification->setUser($requestingUser);
            $confirmationNotification->setType('swap_accepted');
            $confirmationNotification->setTitle('Cambio de Guardia Aceptado');
            
            $message = $count > 1
                ? sprintf(
                    '%s aceptó el cambio de %d guardias. Días: %s',
                    $user->getFullName(),
                    $count,
                    implode(', ', array_column($updatedAssignments, 'date'))
                )
                : sprintf(
                    '%s aceptó el cambio de guardia del %s',
                    $user->getFullName(),
                    $updatedAssignments[0]['date']
                );
            
            $confirmationNotification->setMessage($message);
            $confirmationNotification->setData([
                'type' => 'swap_accepted',
                'assignmentIds' => $assignmentIds,
                'acceptedBy' => [
                    'id' => (string) $user->getId(),
                    'fullName' => $user->getFullName(),
                ],
                'count' => $count,
            ]);
            $this->entityManager->persist($confirmationNotification);
        
        // Notificar a los managers del departamento sobre la aceptación
        $requestingUserDepartment = $requestingUser->getDepartment();
        if ($requestingUserDepartment) {
            // Obtener todos los usuarios del departamento
            $departmentUsers = $this->entityManager->getRepository(User::class)->findBy([
                'department' => $requestingUserDepartment,
            ]);
            
            foreach ($departmentUsers as $manager) {
                $roles = $manager->getRoles();
                // Verificar si es SOLO MANAGER (no ADMIN)
                if (in_array('ROLE_MANAGER', $roles, true) && !in_array('ROLE_ADMIN', $roles, true)) {
                    // No notificar a los usuarios involucrados
                    if ($manager->getId() !== $requestingUser->getId() && $manager->getId() !== $user->getId()) {
                        $managerNotification = new \App\Entity\Notification();
                        $managerNotification->setUser($manager);
                        $managerNotification->setType('swap_accepted');
                        $managerNotification->setTitle('Cambio de Guardia Aceptado (Información)');
                        $managerNotification->setMessage(
                            sprintf(
                                '%s aceptó el cambio de %d guardia(s) solicitado por %s',
                                $user->getFullName(),
                                $count,
                                $requestingUser->getFullName()
                            )
                        );
                        $managerNotification->setData([
                            'type' => 'swap_accepted_info',
                            'acceptedBy' => [
                                'id' => (string) $user->getId(),
                                'fullName' => $user->getFullName(),
                            ],
                            'requestedBy' => [
                                'id' => (string) $requestingUser->getId(),
                                'fullName' => $requestingUser->getFullName(),
                            ],
                            'count' => $count,
                        ]);
                        $this->entityManager->persist($managerNotification);
                        error_log('📬 MANAGER ACCEPT NOTIFICATION - Sent to: ' . $manager->getEmail() . ' (Roles: ' . implode(', ', $roles) . ')');
                    }
                }
            }
        }
        }

        $this->entityManager->flush();

        $message = $count > 1
            ? sprintf('Cambio de %d guardias aceptado exitosamente', $count)
            : 'Cambio de guardia aceptado exitosamente';

        return new JsonResponse([
            'message' => $message,
            'count' => $count,
            'assignments' => $updatedAssignments,
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/reject-swap', name: 'api_notifications_reject_swap', methods: ['POST'])]
    public function rejectSwap(
        Notification $notification,
        Request $request,
        #[CurrentUser] UserInterface $user
    ): JsonResponse {
        // Verificar que la notificación es para este usuario
        if ($notification->getUser()->getId() !== $user->getId()) {
            return new JsonResponse([
                'error' => 'Unauthorized',
            ], Response::HTTP_FORBIDDEN);
        }

        // Verificar que es una notificación de swap
        $data = $notification->getData();
        if (!$data || $data['type'] !== 'swap_request') {
            return new JsonResponse([
                'error' => 'Invalid notification type',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Obtener IDs de asignaciones
        $assignmentIds = $data['assignmentIds'] ?? [$data['assignmentId']];
        $count = count($assignmentIds);

        // Obtener razón del rechazo (opcional)
        $requestData = json_decode($request->getContent(), true);
        $reason = $requestData['reason'] ?? 'Sin motivo especificado';

        // Marcar notificación como leída
        $notification->setRead(true);

        // Crear notificación para el usuario que solicitó el cambio
        $requestingUser = $this->entityManager->getRepository(User::class)->find($data['requestedBy']['id']);
        if ($requestingUser) {
            $rejectionNotification = new \App\Entity\Notification();
            $rejectionNotification->setUser($requestingUser);
            $rejectionNotification->setType('swap_rejected');
            $rejectionNotification->setTitle('Cambio de Guardia Rechazado');
            
            $message = $count > 1
                ? sprintf(
                    '%s rechazó el cambio de %d guardias. Motivo: %s',
                    $user->getFullName(),
                    $count,
                    $reason
                )
                : sprintf(
                    '%s rechazó el cambio de guardia. Motivo: %s',
                    $user->getFullName(),
                    $reason
                );
            
            $rejectionNotification->setMessage($message);
            $rejectionNotification->setData([
                'type' => 'swap_rejected',
                'assignmentIds' => $assignmentIds,
                'rejectedBy' => [
                    'id' => (string) $user->getId(),
                    'fullName' => $user->getFullName(),
                ],
                'reason' => $reason,
                'count' => $count,
            ]);
            $this->entityManager->persist($rejectionNotification);
            
            // Notificar a los managers del departamento sobre el rechazo
            $requestingUserDepartment = $requestingUser->getDepartment();
            if ($requestingUserDepartment) {
                // Obtener todos los usuarios del departamento
                $departmentUsers = $this->entityManager->getRepository(User::class)->findBy([
                    'department' => $requestingUserDepartment,
                ]);
                
                foreach ($departmentUsers as $manager) {
                    $roles = $manager->getRoles();
                    // Verificar si es SOLO MANAGER (no ADMIN)
                    if (in_array('ROLE_MANAGER', $roles, true) && !in_array('ROLE_ADMIN', $roles, true)) {
                        // No notificar a los usuarios involucrados
                        if ($manager->getId() !== $requestingUser->getId() && $manager->getId() !== $user->getId()) {
                            $managerNotification = new \App\Entity\Notification();
                            $managerNotification->setUser($manager);
                            $managerNotification->setType('swap_rejected');
                            $managerNotification->setTitle('Cambio de Guardia Rechazado (Información)');
                            $managerNotification->setMessage(
                                sprintf(
                                    '%s rechazó el cambio de %d guardia(s) solicitado por %s. Motivo: %s',
                                    $user->getFullName(),
                                    $count,
                                    $requestingUser->getFullName(),
                                    $reason
                                )
                            );
                            $managerNotification->setData([
                                'type' => 'swap_rejected_info',
                                'rejectedBy' => [
                                    'id' => (string) $user->getId(),
                                    'fullName' => $user->getFullName(),
                                ],
                                'requestedBy' => [
                                    'id' => (string) $requestingUser->getId(),
                                    'fullName' => $requestingUser->getFullName(),
                                ],
                                'reason' => $reason,
                                'count' => $count,
                            ]);
                            $this->entityManager->persist($managerNotification);
                            error_log('📬 MANAGER REJECT NOTIFICATION - Sent to: ' . $manager->getEmail() . ' (Roles: ' . implode(', ', $roles) . ')');
                        }
                    }
                }
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Cambio de guardia rechazado',
            'count' => $count,
        ], Response::HTTP_OK);
    }
}
