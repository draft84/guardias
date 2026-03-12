<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Task;
use App\Entity\Shift;
use App\Service\TaskService;
use App\Repository\TaskRepository;
use App\Repository\DepartmentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/api/tasks')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class TaskController extends AbstractController
{
    public function __construct(
        private TaskService $taskService,
        private TaskRepository $taskRepository,
        private DepartmentRepository $departmentRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'api_tasks_list', methods: ['GET'])]
    public function list(
        Request $request,
        #[CurrentUser] UserInterface $user
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $status = $request->query->get('status');
        $departmentId = $request->query->get('department');
        
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $isManager = in_array('ROLE_MANAGER', $user->getRoles(), true);
        
        $tasks = [];
        
        // ADMIN puede ver todas las tareas
        if ($isAdmin) {
            if ($departmentId) {
                $department = $this->departmentRepository->find($departmentId);
                if ($department) {
                    $tasks = $this->taskService->getTasksForDepartment($department, $status);
                }
            } else {
                $tasks = $this->taskRepository->findAll();
                if ($status) {
                    $tasks = array_filter($tasks, fn($t) => $t->getStatus() === $status);
                }
            }
        } 
        // MANAGER puede ver las tareas de su departamento
        elseif ($isManager) {
            $userDepartment = $user->getDepartment();
            if ($userDepartment) {
                $tasks = $this->taskService->getTasksForDepartment($userDepartment, $status);
            }
        } 
        // USER solo ve las tareas asignadas o creadas por él
        else {
            $tasks = $this->taskService->getTasksForUser($user, $status);
        }
        
        $data = array_map(function (Task $task) {
            return [
                'id' => (string) $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'startTime' => $task->getStartTime()?->format('H:i'),
                'endTime' => $task->getEndTime()?->format('H:i'),
                'observations' => $task->getObservations(),
                'status' => $task->getStatus(),
                'isDaily' => $task->isDaily(),
                'department' => [
                    'id' => (string) $task->getDepartment()->getId(),
                    'name' => $task->getDepartment()->getName(),
                    'code' => $task->getDepartment()->getCode(),
                ],
                'shift' => [
                    'id' => (string) $task->getShift()->getId(),
                    'name' => $task->getShift()->getName(),
                    'code' => $task->getShift()->getCode(),
                    'type' => $task->getShift()->getType(),
                    'color' => $task->getShift()->getColor(),
                ],
                'createdBy' => [
                    'id' => (string) $task->getCreatedBy()->getId(),
                    'fullName' => $task->getCreatedBy()->getFullName(),
                    'email' => $task->getCreatedBy()->getEmail(),
                ],
                'createdAt' => $task->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $tasks);
        
        return new JsonResponse(['tasks' => array_values($data)], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_tasks_get', methods: ['GET'])]
    public function get(Task $task): JsonResponse
    {
        $this->checkPermissions($task);
        
        $data = [
            'id' => (string) $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'startTime' => $task->getStartTime()?->format('H:i'),
            'endTime' => $task->getEndTime()?->format('H:i'),
            'observations' => $task->getObservations(),
            'status' => $task->getStatus(),
            'department' => [
                'id' => (string) $task->getDepartment()->getId(),
                'name' => $task->getDepartment()->getName(),
            ],
            'shift' => [
                'id' => (string) $task->getShift()->getId(),
                'name' => $task->getShift()->getName(),
                'type' => $task->getShift()->getType(),
            ],
            'createdBy' => [
                'id' => (string) $task->getCreatedBy()->getId(),
                'fullName' => $task->getCreatedBy()->getFullName(),
            ],
            'createdAt' => $task->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];
        
        return new JsonResponse(['task' => $data], Response::HTTP_OK);
    }

    #[Route('', name: 'api_tasks_create', methods: ['POST'])]
    public function create(
        Request $request,
        #[CurrentUser] UserInterface $user
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $data = json_decode($request->getContent(), true);

        // Verificar permisos
        $this->checkCreatePermissions($user, $data['departmentId'] ?? null);

        $department = $this->departmentRepository->find($data['departmentId']);
        if (!$department) {
            return new JsonResponse([
                'error' => 'Department not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $shift = $this->entityManager->getRepository(Shift::class)->find($data['shiftId']);
        if (!$shift) {
            return new JsonResponse([
                'error' => 'Shift not found',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $task = $this->taskService->createTask(
                $data['title'],
                $data['description'],
                new \DateTime($data['startTime']),
                new \DateTime($data['endTime']),
                $department,
                $shift,
                $user,
                $data['observations'] ?? null
            );

            return new JsonResponse([
                'message' => 'Task created successfully',
                'task' => [
                    'id' => (string) $task->getId(),
                    'title' => $task->getTitle(),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'api_tasks_update', methods: ['PUT'])]
    public function update(
        Request $request,
        Task $task,
        #[CurrentUser] UserInterface $user
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $this->checkPermissions($task);

        $data = json_decode($request->getContent(), true);

        $shift = null;
        if (!empty($data['shiftId'])) {
            $shift = $this->entityManager->getRepository(Shift::class)->find($data['shiftId']);
            if (!$shift) {
                return new JsonResponse([
                    'error' => 'Shift not found',
                ], Response::HTTP_NOT_FOUND);
            }
        }

        try {
            $this->taskService->updateTask(
                $task,
                $data['title'] ?? null,
                $data['description'] ?? null,
                isset($data['startTime']) ? new \DateTime($data['startTime']) : null,
                isset($data['endTime']) ? new \DateTime($data['endTime']) : null,
                $shift,
                $data['observations'] ?? null
            );

            return new JsonResponse([
                'message' => 'Task updated successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/status', name: 'api_tasks_update_status', methods: ['PUT'])]
    public function updateStatus(
        Request $request,
        Task $task,
        #[CurrentUser] UserInterface $user
    ): JsonResponse {
        $this->checkPermissions($task);
        
        $data = json_decode($request->getContent(), true);
        
        try {
            $this->taskService->updateTaskStatus(
                $task,
                $data['status'],
                $data['completionNotes'] ?? null
            );
            
            return new JsonResponse([
                'message' => 'Task status updated successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'api_tasks_delete', methods: ['DELETE'])]
    public function delete(Task $task): JsonResponse
    {
        $this->checkPermissions($task);
        
        $this->taskService->deleteTask($task);
        
        return new JsonResponse([
            'message' => 'Task deleted successfully',
        ], Response::HTTP_OK);
    }

    private function checkPermissions(Task $task): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        if (!$user) {
            throw $this->createAccessDeniedException();
        }
        
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $isManager = in_array('ROLE_MANAGER', $user->getRoles(), true);
        
        // ADMIN puede ver todas las tareas
        if ($isAdmin) {
            return;
        }
        
        // MANAGER puede ver tareas de su departamento
        if ($isManager && $user->getDepartment() === $task->getDepartment()) {
            return;
        }
        
        // USER puede ver tareas asignadas o creadas por él
        if ($task->getCreatedBy() === $user || $task->getAssignedTo() === $user) {
            return;
        }
        
        throw $this->createAccessDeniedException();
    }

    private function checkCreatePermissions(\App\Entity\User $user, ?string $departmentId): void
    {
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $isManager = in_array('ROLE_MANAGER', $user->getRoles(), true);

        // ADMIN puede crear tareas para cualquier departamento
        if ($isAdmin) {
            return;
        }

        // MANAGER solo puede crear tareas para su departamento
        if ($isManager) {
            if ($departmentId && $user->getDepartment() && (string) $user->getDepartment()->getId() === $departmentId) {
                return;
            }
            throw $this->createAccessDeniedException('You can only create tasks for your own department');
        }

        // USER no puede crear tareas
        throw $this->createAccessDeniedException('Only ADMIN and MANAGER can create tasks');
    }
}
