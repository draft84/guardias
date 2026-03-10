<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
}
