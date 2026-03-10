<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Department;
use App\Entity\User;
use App\Service\DepartmentService;
use App\Traits\CurrentUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/departments')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DepartmentController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private DepartmentService $departmentService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'api_departments_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $departments = $this->departmentService->getAllDepartments();

        $data = array_map(function (Department $department) {
            return [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
                'code' => $department->getCode(),
                'description' => $department->getDescription(),
                'active' => $department->isActive(),
                'createdAt' => $department->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $department->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'parentDepartment' => $department->getParentDepartment()?->getId() ? (string) $department->getParentDepartment()->getId() : null,
            ];
        }, $departments);

        return new JsonResponse(['departments' => $data], Response::HTTP_OK);
    }

    #[Route('/active', name: 'api_departments_active', methods: ['GET'])]
    public function listActive(): JsonResponse
    {
        $departments = $this->departmentService->getActiveDepartments();

        $data = array_map(function (Department $department) {
            return [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
                'code' => $department->getCode(),
                'description' => $department->getDescription(),
                'active' => $department->isActive(),
            ];
        }, $departments);

        return new JsonResponse(['departments' => $data], Response::HTTP_OK);
    }

    /**
     * Descargar plantilla Excel para carga masiva
     */
    #[Route('/export-template', name: 'api_departments_export_template', methods: ['GET'])]
    public function downloadTemplate(): Response
    {
        $content = $this->departmentService->createTemplate();

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="plantilla_departamentos.xlsx"'
        ]);
    }

    /**
     * Exportar departamentos a Excel
     */
    #[Route('/export', name: 'api_departments_export', methods: ['GET'])]
    public function exportDepartments(): Response
    {
        // Verificar permisos - solo ADMIN puede exportar
        $user = $this->getUser();
        if (!$user instanceof User || !in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new JsonResponse(
                ['error' => 'Acceso denegado. Solo ADMIN puede exportar departamentos'],
                Response::HTTP_FORBIDDEN
            );
        }

        $content = $this->departmentService->exportDepartments();

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="departamentos_export_' . date('Y-m-d') . '.xlsx"'
        ]);
    }

    /**
     * Importar departamentos desde Excel
     */
    #[Route('/import', name: 'api_departments_import', methods: ['POST'])]
    public function importDepartments(Request $request): JsonResponse
    {
        // Verificar permisos - solo ADMIN puede importar
        $user = $this->getUser();
        if (!$user instanceof User || !in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return new JsonResponse(
                ['error' => 'Acceso denegado. Solo ADMIN puede importar departamentos'],
                Response::HTTP_FORBIDDEN
            );
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
            $result = $this->departmentService->importDepartments($file);

            if ($result['success'] === 0 && count($result['errors']) > 0) {
                return new JsonResponse([
                    'error' => 'No se pudo importar ningún departamento',
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

    #[Route('/{id}', name: 'api_departments_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $department = $this->departmentService->getDepartmentById($id);

        if (!$department) {
            return new JsonResponse(['error' => 'Department not found or access denied'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => (string) $department->getId(),
            'name' => $department->getName(),
            'code' => $department->getCode(),
            'description' => $department->getDescription(),
            'active' => $department->isActive(),
            'createdAt' => $department->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $department->getUpdatedAt()?->format('Y-m-d H:i:s'),
            'parentDepartment' => $department->getParentDepartment()?->getId() ? (string) $department->getParentDepartment()->getId() : null,
        ];

        return new JsonResponse(['department' => $data], Response::HTTP_OK);
    }

    #[Route('', name: 'api_departments_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Verificar permisos de escritura
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error;
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['name']) || empty($data['code'])) {
            return new JsonResponse([
                'error' => 'Name and code are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $department = new Department();
        $department->setName($data['name']);
        $department->setCode($data['code']);
        $department->setDescription($data['description'] ?? null);
        $department->setActive($data['active'] ?? true);

        // Si es MANAGER, asignar automáticamente su departamento
        $user = $this->getUser();
        if ($user instanceof User && in_array('ROLE_MANAGER', $user->getRoles(), true)) {
            $userDepartment = $user->getDepartment();
            if ($userDepartment) {
                $department->setParentDepartment($userDepartment);
            }
        }

        $this->entityManager->persist($department);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Department created successfully',
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
                'code' => $department->getCode(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_departments_update', methods: ['PUT'])]
    public function update(Request $request, Department $department): JsonResponse
    {
        // Verificar permisos de escritura
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error;
        }

        // Verificar si puede gestionar este departamento
        $error = $this->canManageDepartment($department);
        if ($error) {
            return $error;
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $department->setName($data['name']);
        }
        if (isset($data['code'])) {
            $department->setCode($data['code']);
        }
        if (isset($data['description'])) {
            $department->setDescription($data['description']);
        }
        if (isset($data['active'])) {
            $department->setActive($data['active']);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Department updated successfully',
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
                'code' => $department->getCode(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_departments_delete', methods: ['DELETE'])]
    public function delete(Department $department): JsonResponse
    {
        // Verificar permisos de escritura
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error;
        }

        // Verificar si puede gestionar este departamento
        $error = $this->canManageDepartment($department);
        if ($error) {
            return $error;
        }

        $this->entityManager->remove($department);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Department deleted successfully',
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/users', name: 'api_departments_users', methods: ['GET'])]
    public function getUsers(Department $department): JsonResponse
    {
        // Verificar si puede ver este departamento
        $error = $this->canManageDepartment($department);
        if ($error) {
            return $error;
        }

        $users = $department->getUsers();

        $data = array_map(function ($user) {
            return [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'active' => $user->isActive(),
            ];
        }, $users);

        return new JsonResponse(['users' => $data], Response::HTTP_OK);
    }
}
