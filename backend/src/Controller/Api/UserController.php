<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\UserService;
use App\Service\UserImportExportService;
use App\Traits\CurrentUserTrait;
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
    use CurrentUserTrait;

    public function __construct(
        private UserService $userService,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private PhoneNumberUtil $phoneNumberUtil,
        private UserImportExportService $importExportService
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
        $users = $this->userService->getAllUsers();

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
        $users = $this->userService->getActiveUsers();

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

    /**
     * Descargar plantilla Excel para carga masiva
     */
    #[Route('/export-template', name: 'api_users_export_template', methods: ['GET'])]
    public function downloadTemplate(): Response
    {
        $content = $this->importExportService->createTemplate();
        
        // Guardar en directorio público para descarga directa
        $templatePath = $this->getParameter('kernel.project_dir') . '/public/templates/plantilla_usuarios.xlsx';
        
        // Crear directorio si no existe
        $dir = dirname($templatePath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Guardar archivo
        file_put_contents($templatePath, $content);

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="plantilla_usuarios.xlsx"'
        ]);
    }

    /**
     * Exportar usuarios a Excel
     */
    #[Route('/export', name: 'api_users_export', methods: ['GET'])]
    public function exportUsers(): Response
    {
        // Verificar permisos - solo ADMIN o MANAGER pueden exportar
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error;
        }

        $users = $this->userService->getAllUsers();
        $content = $this->importExportService->exportUsers($users);

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="usuarios_export_' . date('Y-m-d') . '.xlsx"'
        ]);
    }

    /**
     * Importar usuarios desde Excel
     */
    #[Route('/import', name: 'api_users_import', methods: ['POST'])]
    public function importUsers(Request $request): JsonResponse
    {
        // Verificar permisos - solo ADMIN o MANAGER pueden importar
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error;
        }

        $files = $request->files->all();
        
        if (!isset($files['file'])) {
            return new JsonResponse([
                'error' => 'No se encontró el archivo'
            ], Response::HTTP_BAD_REQUEST);
        }

        $file = $files['file'];

        // Validar tipo de archivo
        $allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel'
        ];

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return new JsonResponse([
                'error' => 'El archivo debe ser un Excel (.xlsx o .xls)'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->importExportService->importUsers($file, '');

            if ($result['success'] === 0 && count($result['errors']) > 0) {
                return new JsonResponse([
                    'error' => 'No se pudo importar ningún usuario',
                    'details' => $result['errors']
                ], Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse([
                'message' => 'Importación completada',
                'success' => $result['success'],
                'total' => $result['total'],
                'errors' => $result['errors']
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Error al importar: ' . $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'api_users_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found or access denied'], Response::HTTP_NOT_FOUND);
        }

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
        // Verificar permisos de escritura
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error;
        }

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
                // Verificar que el MANAGER solo pueda crear usuarios en su departamento
                $error = $this->canManageDepartment($department);
                if ($error) {
                    return $error;
                }
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
        // Verificar permisos de escritura
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error;
        }

        // Verificar si puede gestionar este usuario
        $error = $this->canManageUser($user);
        if ($error) {
            return $error;
        }

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
                    // Verificar que el MANAGER solo pueda asignar usuarios a su departamento
                    $error = $this->canManageDepartment($department);
                    if ($error) {
                        return $error;
                    }
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
        // Verificar permisos de escritura
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error;
        }

        // Verificar si puede gestionar este usuario
        $error = $this->canManageUser($user);
        if ($error) {
            return $error;
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'User deleted successfully',
        ], Response::HTTP_OK);
    }

    #[Route('/department/{departmentId}', name: 'api_users_by_department', methods: ['GET'])]
    public function getByDepartment(string $departmentId): JsonResponse
    {
        // Verificar autenticación
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Usuario no autenticado'],
                Response::HTTP_UNAUTHORIZED
            );
        }
        
        // ADMIN puede ver todos los departamentos
        // MANAGER y USER solo pueden ver usuarios de su departamento
        $is_admin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $userDepartment = $user->getDepartment();
        
        // Determinar qué departamento buscar
        $targetDepartmentId = $departmentId;
        
        // Si no es ADMIN y no proporcionó departamento o es diferente al suyo, usar el suyo
        if (!$is_admin) {
            if (!$userDepartment) {
                return new JsonResponse(
                    ['error' => 'El usuario no pertenece a ningún departamento'],
                    Response::HTTP_FORBIDDEN
                );
            }
            // Forzar que solo vea usuarios de su departamento
            $targetDepartmentId = (string) $userDepartment->getId();
        }
        
        // Obtener todos los usuarios y filtrar
        $allUsers = $this->userService->getAllUsers();
        
        // Filtrar por departamento
        $users = array_filter($allUsers, function ($u) use ($targetDepartmentId) {
            return $u->getDepartment() && (string) $u->getDepartment()->getId() === $targetDepartmentId;
        });

        $data = array_map(function (User $u) {
            return [
                'id' => (string) $u->getId(),
                'email' => $u->getEmail(),
                'firstName' => $u->getFirstName(),
                'lastName' => $u->getLastName(),
                'fullName' => $u->getFullName(),
                'active' => $u->isActive(),
            ];
        }, $users);

        return new JsonResponse(['users' => array_values($data)], Response::HTTP_OK);
    }
}
