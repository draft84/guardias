<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Department;
use App\Repository\DepartmentRepository;
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
    public function __construct(
        private DepartmentRepository $departmentRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'api_departments_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $departments = $this->departmentRepository->findAll();
        
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
        $departments = $this->departmentRepository->findActiveDepartments();
        
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

    #[Route('/{id}', name: 'api_departments_get', methods: ['GET'])]
    public function get(Department $department): JsonResponse
    {
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
        $this->entityManager->remove($department);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Department deleted successfully',
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/users', name: 'api_departments_users', methods: ['GET'])]
    public function getUsers(Department $department): JsonResponse
    {
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
