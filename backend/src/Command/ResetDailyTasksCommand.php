<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tasks:reset-daily',
    description: 'Resetear tareas diarias para el próximo día',
)]
class ResetDailyTasksCommand extends Command
{
    public function __construct(
        private TaskRepository $taskRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Obtener todas las tareas diarias completadas
        $tasks = $this->taskRepository->findAll();
        $dailyTasks = array_filter($tasks, fn($t) => $t->isDaily());

        $resetCount = 0;
        foreach ($dailyTasks as $task) {
            if ($task->getStatus() === 'completed') {
                $task->setStatus('pending');
                $resetCount++;
            }
        }

        if ($resetCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Se resetearon %d tareas diarias para el próximo día.', $resetCount));
        } else {
            $io->info('No hay tareas diarias para resetear.');
        }

        return Command::SUCCESS;
    }
}
