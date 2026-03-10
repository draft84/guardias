<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Role;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/roles')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class RoleController extends AbstractController
{
    public function __construct(
        private RoleRepository $roleRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'api_roles_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $roles = $this->roleRepository->findAll();

        $data = array_map(function (Role $role) {
            return [
                'id' => (string) $role->getId(),
                'name' => $role->getName(),
                'description' => $role->getDescription(),
                'active' => $role->isActive(),
                'isUsed' => false, // Se podría verificar si hay usuarios con este rol
            ];
        }, $roles);

        return new JsonResponse(['roles' => $data], Response::HTTP_OK);
    }

    #[Route('', name: 'api_roles_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Verificar permisos - solo ADMIN o MANAGER pueden crear roles
        $user = $this->getUser();
        if (!in_array('ROLE_ADMIN', $user->getRoles(), true) && !in_array('ROLE_MANAGER', $user->getRoles(), true)) {
            return new JsonResponse([
                'error' => 'Acceso denegado. Se requieren permisos de MANAGER o ADMIN',
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return new JsonResponse([
                'error' => 'El nombre del rol es requerido',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verificar si el rol ya existe
        $existingRole = $this->roleRepository->findOneBy(['name' => $data['name']]);
        if ($existingRole) {
            return new JsonResponse([
                'error' => 'El rol ya existe',
            ], Response::HTTP_CONFLICT);
        }

        $role = new Role();
        $role->setName($data['name']);
        $role->setDescription($data['description'] ?? null);
        $role->setActive($data['active'] ?? true);

        $this->entityManager->persist($role);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Rol creado exitosamente',
            'role' => [
                'id' => (string) $role->getId(),
                'name' => $role->getName(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_roles_update', methods: ['PUT'])]
    public function update(Request $request, Role $role): JsonResponse
    {
        // Verificar permisos
        $user = $this->getUser();
        if (!in_array('ROLE_ADMIN', $user->getRoles(), true) && !in_array('ROLE_MANAGER', $user->getRoles(), true)) {
            return new JsonResponse([
                'error' => 'Acceso denegado. Se requieren permisos de MANAGER o ADMIN',
            ], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            // Verificar que el nuevo nombre no esté en uso por otro rol
            $existingRole = $this->roleRepository->findOneBy(['name' => $data['name']]);
            if ($existingRole && $existingRole->getId() !== $role->getId()) {
                return new JsonResponse([
                    'error' => 'El nombre del rol ya está en uso',
                ], Response::HTTP_CONFLICT);
            }
            $role->setName($data['name']);
        }

        if (isset($data['description'])) {
            $role->setDescription($data['description']);
        }

        if (isset($data['active'])) {
            $role->setActive($data['active']);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Rol actualizado exitosamente',
            'role' => [
                'id' => (string) $role->getId(),
                'name' => $role->getName(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_roles_delete', methods: ['DELETE'])]
    public function delete(Role $role): JsonResponse
    {
        // Verificar permisos
        $user = $this->getUser();
        if (!in_array('ROLE_ADMIN', $user->getRoles(), true) && !in_array('ROLE_MANAGER', $user->getRoles(), true)) {
            return new JsonResponse([
                'error' => 'Acceso denegado. Se requieren permisos de MANAGER o ADMIN',
            ], Response::HTTP_FORBIDDEN);
        }

        // TODO: Verificar si el rol está en uso por algún usuario
        // Por ahora, permitimos eliminar pero en producción se debería verificar

        $this->entityManager->remove($role);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Rol eliminado exitosamente',
        ], Response::HTTP_OK);
    }
}
