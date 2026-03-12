<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\Department;
use App\Entity\Shift;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;

class TaskService
{
    public function __construct(
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @return Task[]
     */
    public function getTasksForUser(User $user, ?string $status = null): array
    {
        $tasks = $this->taskRepository->findByUser($user);
        
        if ($status) {
            $tasks = array_filter($tasks, fn($t) => $t->getStatus() === $status);
        }
        
        return $tasks;
    }

    /**
     * @return Task[]
     */
    public function getTasksForDepartment(Department $department, ?string $status = null): array
    {
        $tasks = $this->taskRepository->findByDepartment($department);
        
        if ($status) {
            $tasks = array_filter($tasks, fn($t) => $t->getStatus() === $status);
        }
        
        return $tasks;
    }

    public function createTask(
        string $title,
        string $description,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        Department $department,
        Shift $shift,
        User $createdBy,
        ?string $observations = null
    ): Task {
        $task = new Task();
        $task->setTitle($title);
        $task->setDescription($description);
        $task->setStartTime($startTime);
        $task->setEndTime($endTime);
        $task->setDepartment($department);
        $task->setShift($shift);
        $task->setCreatedBy($createdBy);
        $task->setObservations($observations);
        $task->setStatus('pending');

        $this->taskRepository->save($task, true);

        return $task;
    }

    public function updateTask(
        Task $task,
        ?string $title = null,
        ?string $description = null,
        ?\DateTimeInterface $startTime = null,
        ?\DateTimeInterface $endTime = null,
        ?Shift $shift = null,
        ?string $observations = null
    ): Task {
        if ($title !== null) {
            $task->setTitle($title);
        }
        if ($description !== null) {
            $task->setDescription($description);
        }
        if ($startTime !== null) {
            $task->setStartTime($startTime);
        }
        if ($endTime !== null) {
            $task->setEndTime($endTime);
        }
        if ($shift !== null) {
            $task->setShift($shift);
        }
        if ($observations !== null) {
            $task->setObservations($observations);
        }

        $this->taskRepository->save($task, true);

        return $task;
    }

    public function updateTaskStatus(Task $task, string $status, ?string $completionNotes = null): Task
    {
        $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: $status");
        }

        $task->setStatus($status);
        
        if ($status === 'completed') {
            $task->setCompletedAt(new \DateTime());
            $task->setCompletionNotes($completionNotes);
        } elseif ($status === 'in_progress') {
            $task->setCompletedAt(null);
            $task->setCompletionNotes(null);
        }

        $this->taskRepository->save($task, true);

        return $task;
    }

    public function deleteTask(Task $task): void
    {
        $this->taskRepository->remove($task, true);
    }
}
