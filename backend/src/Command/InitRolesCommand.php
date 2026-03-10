<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-roles',
    description: 'Inicializa los roles por defecto en la base de datos',
)]
class InitRolesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rolesData = [
            ['name' => 'ROLE_ADMIN', 'description' => 'Administrador con acceso total'],
            ['name' => 'ROLE_MANAGER', 'description' => 'Manager de departamento'],
            ['name' => 'ROLE_USER', 'description' => 'Usuario estándar'],
        ];

        $connection = $this->entityManager->getConnection();
        $created = 0;

        foreach ($rolesData as $roleData) {
            // Verificar si el rol ya existe
            $existing = $connection->fetchOne('SELECT COUNT(*) FROM roles WHERE name = :name', ['name' => $roleData['name']]);
            
            if ($existing == 0) {
                // Generar UUID v4 válido para MySQL
                $uuid = sprintf(
                    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff)
                );
                
                $connection->insert('roles', [
                    'id' => $uuid,
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $created++;
                $io->writeln(sprintf('  ✓ Rol creado: <info>%s</info>', $roleData['name']));
            } else {
                $io->writeln(sprintf('  - Rol ya existe: <comment>%s</comment>', $roleData['name']));
            }
        }

        if ($created > 0) {
            $io->success(sprintf('%d rol(es) creado(s) exitosamente.', $created));
        } else {
            $io->info('No se crearon roles nuevos.');
        }

        return Command::SUCCESS;
    }
}
