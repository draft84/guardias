<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Guard;
use App\Service\GuardService;
use App\Traits\CurrentUserTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/guards')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GuardController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private GuardService $guardService,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'api_guards_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $guards = $this->guardService->getAllGuards();

        $data = array_map(function (Guard $guard) {
            $firstAssignment = $guard->getAssignments()->first();
            return [
                'id' => (string) $guard->getId(),
                'name' => $guard->getName(),
                'code' => $guard->getCode(),
                'description' => $guard->getDescription(),
                'department' => $guard->getDepartment()?->getId() ? (string) $guard->getDepartment()->getId() : null,
                'departmentName' => $guard->getDepartment()?->getName(),
                'userId' => $firstAssignment ? (string) $firstAssignment->getUser()?->getId() : null,
                'startTime' => $guard->getStartTime()?->format('H:i'),
                'endTime' => $guard->getEndTime()?->format('H:i'),
                'weekDays' => $guard->getWeekDays() ?? [],
                'validFrom' => $guard->getValidFrom()?->format('Y-m-d'),
                'validUntil' => $guard->getValidUntil()?->format('Y-m-d'),
                'duration' => $guard->getDuration(),
                'active' => $guard->isActive(),
                'createdAt' => $guard->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $guards);

        return new JsonResponse(['guards' => $data], Response::HTTP_OK);
    }

    #[Route('/active', name: 'api_guards_active', methods: ['GET'])]
    public function listActive(): JsonResponse
    {
        $guards = $this->guardService->getActiveGuards();

        $data = array_map(function (Guard $guard) {
            return [
                'id' => (string) $guard->getId(),
                'name' => $guard->getName(),
                'code' => $guard->getCode(),
                'description' => $guard->getDescription(),
                'department' => $guard->getDepartment()?->getId() ? (string) $guard->getDepartment()->getId() : null,
                'departmentName' => $guard->getDepartment()?->getName(),
                'startTime' => $guard->getStartTime()?->format('H:i'),
                'endTime' => $guard->getEndTime()?->format('H:i'),
                'duration' => $guard->getDuration(),
                'active' => $guard->isActive(),
            ];
        }, $guards);

        return new JsonResponse(['guards' => $data], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_guards_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $guard = $this->guardService->getGuardById($id);
        
        if (!$guard) {
            return new JsonResponse(['error' => 'Guard not found or access denied'], Response::HTTP_NOT_FOUND);
        }
        
        $firstAssignment = $guard->getAssignments()->first();
        $data = [
            'id' => (string) $guard->getId(),
            'name' => $guard->getName(),
            'code' => $guard->getCode(),
            'description' => $guard->getDescription(),
            'department' => $guard->getDepartment()?->getId() ? (string) $guard->getDepartment()->getId() : null,
            'departmentName' => $guard->getDepartment()?->getName(),
            'userId' => $firstAssignment ? (string) $firstAssignment->getUser()?->getId() : null,
            'startTime' => $guard->getStartTime()?->format('H:i'),
            'endTime' => $guard->getEndTime()?->format('H:i'),
            'weekDays' => $guard->getWeekDays() ?? [],
            'validFrom' => $guard->getValidFrom()?->format('Y-m-d'),
            'validUntil' => $guard->getValidUntil()?->format('Y-m-d'),
            'duration' => $guard->getDuration(),
            'active' => $guard->isActive(),
        ];

        return new JsonResponse(['guard' => $data], Response::HTTP_OK);
    }

    #[Route('', name: 'api_guards_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Verificar permisos de escritura
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error;
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['name']) ||
            empty($data['startTime']) || empty($data['endTime'])) {
            return new JsonResponse([
                'error' => 'Name, startTime and endTime are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $guard = new Guard();
        $guard->setName($data['name']);
        $guard->setCode(uniqid('GRD-'));
        $guard->setDescription($data['description'] ?? null);
        $guard->setStartTime(new \DateTime($data['startTime']));
        $guard->setEndTime(new \DateTime($data['endTime']));
        $guard->setActive($data['active'] ?? true);

        // Asignar días de la semana si se proporcionan
        if (isset($data['weekDays']) && is_array($data['weekDays'])) {
            $guard->setWeekDays($data['weekDays']);
        }

        // Asignar fecha de inicio y fin de validez
        if (isset($data['validFrom']) && !empty($data['validFrom'])) {
            $guard->setValidFrom(new \DateTime($data['validFrom']));
        }
        if (isset($data['validUntil']) && !empty($data['validUntil'])) {
            $guard->setValidUntil(new \DateTime($data['validUntil']));
        }

        // Asignar departamento si se proporciona
        if (isset($data['departmentId']) && !empty($data['departmentId'])) {
            $department = $this->entityManager->getRepository(\App\Entity\Department::class)->find($data['departmentId']);
            if ($department) {
                // Verificar que el MANAGER solo pueda crear guardias en su departamento
                $error = $this->canManageDepartment($department);
                if ($error) {
                    return $error;
                }
                $guard->setDepartment($department);
            }
        }

        $this->entityManager->persist($guard);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Guard created successfully',
            'guard' => [
                'id' => (string) $guard->getId(),
                'name' => $guard->getName(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_guards_update', methods: ['PUT'])]
    public function update(Request $request, Guard $guard): JsonResponse
    {
        // Verificar permisos de escritura
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error;
        }

        // Verificar si puede gestionar esta guardia
        $error = $this->canManageGuard($guard);
        if ($error) {
            return $error;
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $guard->setName($data['name']);
        }
        if (isset($data['description'])) {
            $guard->setDescription($data['description']);
        }
        if (isset($data['startTime'])) {
            $guard->setStartTime(new \DateTime($data['startTime']));
        }
        if (isset($data['endTime'])) {
            $guard->setEndTime(new \DateTime($data['endTime']));
        }
        if (isset($data['active'])) {
            $guard->setActive($data['active']);
        }

        // Actualizar días de la semana
        if (array_key_exists('weekDays', $data)) {
            $guard->setWeekDays($data['weekDays']);
        }
        // Actualizar fechas de validez
        if (array_key_exists('validFrom', $data)) {
            if (empty($data['validFrom'])) {
                $guard->setValidFrom(null);
            } else {
                $guard->setValidFrom(new \DateTime($data['validFrom']));
            }
        }
        if (array_key_exists('validUntil', $data)) {
            if (empty($data['validUntil'])) {
                $guard->setValidUntil(null);
            } else {
                $guard->setValidUntil(new \DateTime($data['validUntil']));
            }
        }
        // Actualizar departamento
        if (array_key_exists('departmentId', $data)) {
            if (empty($data['departmentId'])) {
                $guard->setDepartment(null);
            } else {
                $department = $this->entityManager->getRepository(\App\Entity\Department::class)->find($data['departmentId']);
                if ($department) {
                    // Verificar que el MANAGER solo pueda asignar guardias a su departamento
                    $error = $this->canManageDepartment($department);
                    if ($error) {
                        return $error;
                    }
                    $guard->setDepartment($department);
                }
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Guard updated successfully',
            'guard' => [
                'id' => (string) $guard->getId(),
                'name' => $guard->getName(),
                'code' => $guard->getCode(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_guards_delete', methods: ['DELETE'])]
    public function delete(Guard $guard): JsonResponse
    {
        // Verificar permisos de escritura
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error;
        }

        // Verificar si puede gestionar esta guardia
        $error = $this->canManageGuard($guard);
        if ($error) {
            return $error;
        }

        $this->entityManager->remove($guard);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Guard deleted successfully',
        ], Response::HTTP_OK);
    }

    #[Route('/{id}/assignments', name: 'api_guards_assignments', methods: ['GET'])]
    public function getAssignments(Guard $guard): JsonResponse
    {
        // Verificar permisos
        $error = $this->checkWritePermissions();
        if ($error) {
            return $error;
        }
        
        // Verificar si puede ver esta guardia
        $error = $this->canManageGuard($guard);
        if ($error) {
            return $error;
        }

        $assignments = $guard->getAssignments();

        $data = array_map(function ($assignment) {
            $user = $assignment->getUser();
            return [
                'id' => (string) $assignment->getId(),
                'date' => $assignment->getDate()?->format('Y-m-d'),
                'startTime' => $assignment->getStartTime()?->format('H:i'),
                'endTime' => $assignment->getEndTime()?->format('H:i'),
                'status' => $assignment->getStatus(),
                'user' => [
                    'id' => (string) $user->getId(),
                    'fullName' => $user->getFullName(),
                    'email' => $user->getEmail(),
                ],
            ];
        }, $assignments->toArray());

        return new JsonResponse(['assignments' => $data], Response::HTTP_OK);
    }
}
