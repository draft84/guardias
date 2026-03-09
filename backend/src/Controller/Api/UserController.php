<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

#[Route('/api/users')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private PhoneNumberUtil $phoneNumberUtil
    ) {
    }

    /**
     * Valida y parsea un número de teléfono usando libphonenumber.
     * Retorna un PhoneNumber object o null si es inválido.
     */
    private function parsePhone(string $phone, string $defaultRegion = 'VE'): ?\libphonenumber\PhoneNumber
    {
        try {
            $phoneNumber = $this->phoneNumberUtil->parse($phone, $defaultRegion);

            if (!$this->phoneNumberUtil->isValidNumber($phoneNumber)) {
                return null;
            }

            return $phoneNumber;
        } catch (NumberParseException $e) {
            return null;
        }
    }

    private function formatPhone(?\libphonenumber\PhoneNumber $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $formatted = $this->phoneNumberUtil->format($phone, PhoneNumberFormat::NATIONAL);
        // El formato nacional de Venezuela suele venir con espacios (ej: 0412 1234567). Los removemos.
        return str_replace(' ', '', $formatted);
    }

    #[Route('', name: 'api_users_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $users = $this->userRepository->findAll();

        $data = array_map(function (User $user) {
            return [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFullName(),
                'phone' => $this->formatPhone($user->getPhone()),
                'roles' => $user->getRoles(),
                'active' => $user->isActive(),
                'department' => $user->getDepartment()?->getId() ? (string) $user->getDepartment()->getId() : null,
                'departmentName' => $user->getDepartment()?->getName(),
                'guardLevel' => $user->getGuardLevel()?->getName(),
                'guardLevelId' => $user->getGuardLevel()?->getId() ? (string) $user->getGuardLevel()->getId() : null,
                'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $users);

        return new JsonResponse(['users' => $data], Response::HTTP_OK);
    }

    #[Route('/active', name: 'api_users_active', methods: ['GET'])]
    public function listActive(): JsonResponse
    {
        $users = $this->userRepository->findActiveUsers();

        $data = array_map(function (User $user) {
            return [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFullName(),
                'active' => $user->isActive(),
            ];
        }, $users);

        return new JsonResponse(['users' => $data], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_users_get', methods: ['GET'])]
    public function get(User $user): JsonResponse
    {
        $data = [
            'id' => (string) $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'phone' => $this->formatPhone($user->getPhone()),
            'roles' => $user->getRoles(),
            'active' => $user->isActive(),
            'department' => $user->getDepartment()?->getId() ? (string) $user->getDepartment()->getId() : null,
            'departmentName' => $user->getDepartment()?->getName(),
            'guardLevel' => $user->getGuardLevel()?->getName(),
            'guardLevelId' => $user->getGuardLevel()?->getId() ? (string) $user->getGuardLevel()->getId() : null,
            'createdAt' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            'lastLogin' => $user->getLastLogin()?->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse(['user' => $data], Response::HTTP_OK);
    }

    #[Route('', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $required = ['email', 'password', 'firstName', 'lastName', 'phone', 'departmentId', 'roles'];
        $missing = array_filter($required, fn($field) => empty($data[$field]));

        if (!empty($missing)) {
            return new JsonResponse([
                'error' => 'Validation failed: missing required fields',
                'missing' => array_values($missing),
            ], Response::HTTP_BAD_REQUEST);
        }


        // Validar teléfono
        if (!empty($data['phone'])) {
            $phoneNumberObject = $this->parsePhone($data['phone']);
            if ($phoneNumberObject === null) {
                return new JsonResponse([
                    'error' => 'El número de teléfono no es válido',
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $phoneNumberObject = null;
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setPhone($phoneNumberObject);
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);
        $user->setActive($data['active'] ?? true);

        // Asignar departamento si se proporciona
        if (isset($data['departmentId']) && !empty($data['departmentId'])) {
            $department = $this->entityManager->getRepository(\App\Entity\Department::class)->find($data['departmentId']);
            if ($department) {
                $user->setDepartment($department);
            }
        }

        // Asignar nivel de guardia
        if (isset($data['guardLevelId']) && !empty($data['guardLevelId'])) {
            $level = $this->entityManager->getRepository(\App\Entity\GuardLevel::class)->find($data['guardLevelId']);
            if ($level) {
                $user->setGuardLevel($level);
            }
        }

        // Hashear password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Validar
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse([
                'error' => 'Validation failed',
                'details' => (string) $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'User created successfully',
            'user' => [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_users_update', methods: ['PUT'])]
    public function update(Request $request, User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (array_key_exists('phone', $data)) {
            if (!empty($data['phone'])) {
                $phoneNumberObject = $this->parsePhone($data['phone']);
                if ($phoneNumberObject === null) {
                    return new JsonResponse([
                        'error' => 'El número de teléfono no es válido',
                    ], Response::HTTP_BAD_REQUEST);
                }
                $user->setPhone($phoneNumberObject);
            } else {
                $user->setPhone(null);
            }
        }
        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }
        if (isset($data['active'])) {
            $user->setActive($data['active']);
        }
        if (isset($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }
        // Actualizar departamento
        if (array_key_exists('departmentId', $data)) {
            if (empty($data['departmentId'])) {
                $user->setDepartment(null);
            } else {
                $department = $this->entityManager->getRepository(\App\Entity\Department::class)->find($data['departmentId']);
                if ($department) {
                    $user->setDepartment($department);
                }
            }
        }

        // Actualizar nivel de guardia
        if (array_key_exists('guardLevelId', $data)) {
            if (empty($data['guardLevelId'])) {
                $user->setGuardLevel(null);
            } else {
                $level = $this->entityManager->getRepository(\App\Entity\GuardLevel::class)->find($data['guardLevelId']);
                if ($level) {
                    $user->setGuardLevel($level);
                }
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'User updated successfully',
            'user' => [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function delete(User $user): JsonResponse
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'User deleted successfully',
        ], Response::HTTP_OK);
    }

    #[Route('/department/{departmentId}', name: 'api_users_by_department', methods: ['GET'])]
    public function getByDepartment(string $departmentId): JsonResponse
    {
        error_log("=== BUSCANDO USUARIOS PARA DEPT: $departmentId ===");
        $users = $this->userRepository->findByDepartment($departmentId);
        error_log("=== USUARIOS ENCONTRADOS: " . count($users) . " ===");

        $data = array_map(function (User $user) {
            return [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFullName(),
                'active' => $user->isActive(),
            ];
        }, $users);

        return new JsonResponse(['users' => $data], Response::HTTP_OK);
    }
}
