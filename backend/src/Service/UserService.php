<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use libphonenumber\PhoneNumberUtil;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private PhoneNumberUtil $phoneNumberUtil
    ) {
    }

    /**
     * @return User[]
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * @return User[]
     */
    public function getActiveUsers(): array
    {
        return $this->userRepository->findActiveUsers();
    }

    public function getUserById(string $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findOneBy(['email' => $email]);
    }

    /**
     * @return User[]
     */
    public function getUsersByDepartment(string $departmentId): array
    {
        return $this->userRepository->findByDepartment($departmentId);
    }

    public function createUser(
        string $email,
        string $password,
        string $firstName,
        string $lastName,
        ?string $phone = null,
        array $roles = ['ROLE_USER'],
        ?string $departmentId = null,
        bool $active = true
    ): User {
        $phoneNumber = null;
        if ($phone !== null) {
            try {
                $phoneNumber = $this->phoneNumberUtil->parse($phone, 'VE');
            } catch (\Exception $e) {
            }
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPhone($phoneNumber);
        $user->setRoles($roles);
        $user->setActive($active);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateUser(
        User $user,
        ?string $email = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $phone = null,
        ?array $roles = null,
        ?bool $active = null,
        ?string $plainPassword = null
    ): User {
        if ($email !== null) {
            $user->setEmail($email);
        }
        if ($firstName !== null) {
            $user->setFirstName($firstName);
        }
        if ($lastName !== null) {
            $user->setLastName($lastName);
        }
        if ($phone !== null) {
            try {
                $phoneNumber = $this->phoneNumberUtil->parse($phone, 'VE');
                $user->setPhone($phoneNumber);
            } catch (\Exception $e) {
            }
        }
        if ($roles !== null) {
            $user->setRoles($roles);
        }
        if ($active !== null) {
            $user->setActive($active);
        }
        if ($plainPassword !== null) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
        }

        $this->entityManager->flush();

        return $user;
    }

    public function deleteUser(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    public function updateUserPassword(User $user, string $plainPassword): User
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        $this->entityManager->flush();

        return $user;
    }

    public function updateUserLastLogin(User $user): User
    {
        $user->setLastLogin(new \DateTime());
        $this->entityManager->flush();

        return $user;
    }

    public function verifyPassword(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }

    /**
     * @return User[]
     */
    public function getUsersByRole(string $role): array
    {
        $users = $this->getAllUsers();
        return array_filter($users, function (User $user) use ($role) {
            return in_array($role, $user->getRoles());
        });
    }

    public function isUserAdmin(User $user): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    public function isUserManager(User $user): bool
    {
        return in_array('ROLE_MANAGER', $user->getRoles());
    }
}
