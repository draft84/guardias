<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crea un usuario administrador si no existe',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Verificar si ya existe un admin
        $existingAdmin = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        
        if ($existingAdmin) {
            $output->writeln('<info>El usuario administrador ya existe.</info>');
            $output->writeln('<info>Email: admin@example.com</info>');
            $output->writeln('<info>Password: admin123</info>');
            return Command::SUCCESS;
        }

        // Crear usuario administrador
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setFirstName('Administrador');
        $admin->setLastName('Sistema');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setActive(true);
        
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);
        
        $this->entityManager->persist($admin);
        $this->entityManager->flush();
        
        $output->writeln('<info>Usuario administrador creado exitosamente.</info>');
        $output->writeln('');
        $output->writeln('<comment>=== CREDENCIALES DE ACCESO ===</comment>');
        $output->writeln('<comment>Email: admin@example.com</comment>');
        $output->writeln('<comment>Password: admin123</comment>');
        $output->writeln('');
        $output->writeln('<comment>URL de acceso: http://localhost:5173</comment>');
        
        return Command::SUCCESS;
    }
}
