<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Department;
use App\Entity\User;
use App\Entity\Guard;
use App\Entity\GuardLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use libphonenumber\PhoneNumberUtil;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private PhoneNumberUtil $phoneNumberUtil
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Crear departamentos
        $deptIT = new Department();
        $deptIT->setName('Tecnología');
        $deptIT->setCode('TECH');
        $deptIT->setDescription('Departamento de Tecnología');
        $deptIT->setActive(true);
        $manager->persist($deptIT);

        $deptHR = new Department();
        $deptHR->setName('Recursos Humanos');
        $deptHR->setCode('HR');
        $deptHR->setDescription('Departamento de Recursos Humanos');
        $deptHR->setActive(true);
        $manager->persist($deptHR);

        $deptOps = new Department();
        $deptOps->setName('Operaciones');
        $deptOps->setCode('OPS');
        $deptOps->setDescription('Departamento de Operaciones');
        $deptOps->setActive(true);
        $manager->persist($deptOps);

        // Crear niveles de guardia
        $levelJunior = new GuardLevel();
        $levelJunior->setName('Junior');
        $manager->persist($levelJunior);

        $levelSenior = new GuardLevel();
        $levelSenior->setName('Senior');
        $manager->persist($levelSenior);

        $levelResidente = new GuardLevel();
        $levelResidente->setName('Residente');
        $manager->persist($levelResidente);

        $manager->flush();

        $levels = [$levelJunior, $levelSenior, $levelResidente];

        // Roles y nombres de prueba
        $firstNames = ['Juan', 'María', 'Pedro', 'Ana', 'Luis', 'Carmen', 'José', 'Laura', 'Carlos', 'Sofía'];
        $lastNames = ['Pérez', 'García', 'López', 'Martínez', 'Rodríguez', 'Fernández', 'González', 'Gómez', 'Díaz', 'Álvarez'];

        // Crear usuarios
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setFirstName('Administrador');
        $admin->setLastName('Sistema');
        $admin->setPhone($this->phoneNumberUtil->parse('+584120000001', 'VE'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setDepartment($deptIT);
        $admin->setGuardLevel($levelSenior);
        $admin->setActive(true);
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        $manager1 = new User();
        $manager1->setEmail('manager@example.com');
        $manager1->setFirstName('Manager');
        $manager1->setLastName('Operaciones');
        $manager1->setPhone($this->phoneNumberUtil->parse('+584120000002', 'VE'));
        $manager1->setRoles(['ROLE_MANAGER']);
        $manager1->setDepartment($deptOps);
        $manager1->setGuardLevel($levelSenior);
        $manager1->setActive(true);
        $hashedPassword = $this->passwordHasher->hashPassword($manager1, 'manager123');
        $manager1->setPassword($hashedPassword);
        $manager->persist($manager1);

        $allUsers = [];
        $departments = [$deptIT, $deptHR, $deptOps];

        foreach ($departments as $dept) {
            for ($i = 1; $i <= 5; $i++) {
                $user = new User();
                // Generar un email único basado en el departamento y el índice
                $emailPrefix = strtolower($dept->getCode()) . '_user' . $i;
                $user->setEmail($emailPrefix . '@example.com');

                // Nombres aleatorios
                $user->setFirstName($firstNames[array_rand($firstNames)]);
                $user->setLastName($lastNames[array_rand($lastNames)]);

                $user->setPhone($this->phoneNumberUtil->parse('+58412' . str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT), 'VE'));
                $user->setRoles(['ROLE_USER']);
                $user->setDepartment($dept);
                $user->setGuardLevel($levels[array_rand($levels)]);
                $user->setActive(true);

                $hashedPassword = $this->passwordHasher->hashPassword($user, 'user123');
                $user->setPassword($hashedPassword);

                $manager->persist($user);
                $allUsers[] = $user;
            }
        }

        $manager->flush();

        // Crear guardias
        $guard1 = new Guard();
        $guard1->setName('Guardia Matutina');
        $guard1->setCode('GM');
        $guard1->setDescription('Guardia de mañana');
        $guard1->setDepartment($deptOps);
        $guard1->setStartTime(new \DateTime('06:00'));
        $guard1->setEndTime(new \DateTime('14:00'));
        $guard1->setActive(true);
        $manager->persist($guard1);

        $guard2 = new Guard();
        $guard2->setName('Guardia Vespertina');
        $guard2->setCode('GV');
        $guard2->setDescription('Guardia de tarde');
        $guard2->setDepartment($deptOps);
        $guard2->setStartTime(new \DateTime('14:00'));
        $guard2->setEndTime(new \DateTime('22:00'));
        $guard2->setActive(true);
        $manager->persist($guard2);

        $guard3 = new Guard();
        $guard3->setName('Guardia Nocturna');
        $guard3->setCode('GN');
        $guard3->setDescription('Guardia de noche');
        $guard3->setDepartment($deptOps);
        $guard3->setStartTime(new \DateTime('22:00'));
        $guard3->setEndTime(new \DateTime('06:00'));
        $guard3->setActive(true);
        $manager->persist($guard3);

        $manager->flush();

        // Crear asignaciones de guardia para los próximos 7 días
        $users = $allUsers;
        $guards = [$guard1, $guard2, $guard3];

        for ($i = 0; $i < 7; $i++) {
            $date = new \DateTime();
            $date->modify("+{$i} days");

            foreach ($guards as $guard) {
                $user = $users[array_rand($users)];

                $assignment = new \App\Entity\GuardAssignment();
                $assignment->setGuard($guard);
                $assignment->setUser($user);
                $assignment->setAssignedBy($manager1);
                $assignment->setDate($date);
                $assignment->setStartTime($guard->getStartTime());
                $assignment->setEndTime($guard->getEndTime());
                $assignment->setStatus('scheduled');
                $assignment->setNotes('Asignación automática');

                $manager->persist($assignment);
            }
        }

        $manager->flush();
    }
}
