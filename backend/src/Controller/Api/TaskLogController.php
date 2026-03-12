<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\TaskLog;
use App\Repository\TaskLogRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api/task-logs')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class TaskLogController extends AbstractController
{
    public function __construct(
        private TaskLogRepository $taskLogRepository,
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'api_task_logs_list', methods: ['GET'])]
    public function list(
        Request $request,
        #[CurrentUser] UserInterface $user
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $date = $request->query->get('date');

        if (!$date) {
            $date = new \DateTime();
        } else {
            $date = new \DateTime($date);
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $isManager = in_array('ROLE_MANAGER', $user->getRoles(), true);
        $department = $user->getDepartment();

        $logs = [];

        try {
            if ($isAdmin) {
                // ADMIN ve todos los logs de la fecha
                $logs = $this->taskLogRepository->findByDate($date);
            } else {
                // MANAGER y USER ven los logs de su departamento
                if ($department) {
                    $logs = $this->taskLogRepository->findByDate($date);
                    $logs = array_filter($logs, fn($log) =>
                        $log->getTask() &&
                        $log->getTask()->getDepartment() &&
                        $log->getTask()->getDepartment()->getId() === $department->getId()
                    );
                }
                // Si no tiene departamento, ve lista vacía
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = array_map(function (TaskLog $log) {
            return [
                'id' => (string) $log->getId(),
                'task' => $log->getTask() ? [
                    'id' => (string) $log->getTask()->getId(),
                    'title' => $log->getTask()->getTitle(),
                    'description' => $log->getTask()->getDescription(),
                    'startTime' => $log->getTask()->getStartTime()?->format('H:i'),
                    'endTime' => $log->getTask()->getEndTime()?->format('H:i'),
                ] : null,
                'user' => $log->getUser() ? [
                    'id' => (string) $log->getUser()->getId(),
                    'fullName' => $log->getUser()->getFullName(),
                ] : null,
                'date' => $log->getDate()?->format('Y-m-d'),
                'startTime' => $log->getStartTime()?->format('H:i'),
                'verificationTime' => $log->getVerificationTime()?->format('H:i'),
                'observations' => $log->getObservations(),
                'status' => $log->getStatus(),
                'riskLevel' => $log->getRiskLevel(),
                'createdAt' => $log->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $logs);

        return new JsonResponse(['logs' => array_values($data)], Response::HTTP_OK);
    }

    #[Route('', name: 'api_task_logs_create', methods: ['POST'])]
    public function create(
        Request $request,
        #[CurrentUser] UserInterface $user
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $data = json_decode($request->getContent(), true);

        if (empty($data['taskId'])) {
            return new JsonResponse([
                'error' => 'Task ID is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $task = $this->taskRepository->find($data['taskId']);
        if (!$task) {
            return new JsonResponse([
                'error' => 'Task not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $log = new TaskLog();
        $log->setTask($task);
        $log->setUser($user);
        $log->setDate(new \DateTime($data['date'] ?? 'today'));
        $log->setStartTime($task->getStartTime());

        if (!empty($data['verificationTime'])) {
            $log->setVerificationTime(new \DateTime($data['verificationTime']));
        }

        $log->setObservations($data['observations'] ?? null);
        $log->setStatus($data['status'] ?? 'completed');
        $log->setRiskLevel($data['riskLevel'] ?? 'normal');

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Task log created successfully',
            'log' => [
                'id' => (string) $log->getId(),
                'taskId' => (string) $task->getId(),
                'status' => $log->getStatus(),
                'riskLevel' => $log->getRiskLevel(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_task_logs_update', methods: ['PUT'])]
    public function update(
        Request $request,
        TaskLog $taskLog
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (isset($data['verificationTime'])) {
            $taskLog->setVerificationTime(new \DateTime($data['verificationTime']));
        }

        if (isset($data['observations'])) {
            $taskLog->setObservations($data['observations']);
        }

        if (isset($data['status'])) {
            $taskLog->setStatus($data['status']);
        }

        if (isset($data['riskLevel'])) {
            $taskLog->setRiskLevel($data['riskLevel']);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Task log updated successfully',
            'log' => [
                'id' => (string) $taskLog->getId(),
                'status' => $taskLog->getStatus(),
                'riskLevel' => $taskLog->getRiskLevel(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/pending-tasks', name: 'api_task_logs_pending_tasks', methods: ['GET'])]
    public function getPendingTasks(
        Request $request,
        #[CurrentUser] UserInterface $user
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $date = $request->query->get('date');

        if (!$date) {
            $date = new \DateTime();
        } else {
            $date = new \DateTime($date);
        }

        $department = $user->getDepartment();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $isManager = in_array('ROLE_MANAGER', $user->getRoles(), true);

        // Obtener tareas activas
        $tasks = $this->taskRepository->findAll();

        // Filtrar por departamento si no es admin
        if (!$isAdmin) {
            if ($department) {
                // Usuario con departamento: ver solo tareas de su departamento
                $tasks = array_filter($tasks, fn($t) =>
                    $t->getDepartment() &&
                    $t->getDepartment()->getId() === $department->getId()
                );
            } elseif (!$isManager) {
                // Usuario sin departamento y no manager: no ve tareas
                $tasks = [];
            }
            // Si es manager sin departamento, ve todas las tareas
        }

        // Solo tareas pendientes (diarias o no)
        $tasks = array_filter($tasks, fn($t) =>
            $t->getStatus() === 'pending'
        );

        // Obtener logs ya creados hoy
        $logs = $this->taskLogRepository->findByDate($date);
        $loggedTaskIds = array_map(fn($log) => $log->getTask()?->getId(), $logs);

        // Filtrar tareas que ya tienen log hoy
        $pendingTasks = array_filter($tasks, fn($t) =>
            !in_array($t->getId(), $loggedTaskIds)
        );

        $data = array_map(function ($task) {
            return [
                'id' => (string) $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'startTime' => $task->getStartTime()?->format('H:i'),
                'endTime' => $task->getEndTime()?->format('H:i'),
            ];
        }, $pendingTasks);

        return new JsonResponse(['tasks' => array_values($data)], Response::HTTP_OK);
    }
}
