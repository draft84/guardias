<?php

declare(strict_types=1);

namespace App\Traits;

use App\Entity\Department;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait para obtener información del usuario autenticado y verificar permisos
 */
trait CurrentUserTrait
{
    private function getCurrentUser(Security $security): ?User
    {
        $user = $security->getUser();
        
        if (!$user instanceof User) {
            return null;
        }
        
        return $user;
    }

    private function getCurrentUserDepartment(Security $security): ?string
    {
        $user = $this->getCurrentUser($security);
        
        if (!$user) {
            return null;
        }
        
        $department = $user->getDepartment();
        
        if (!$department) {
            return null;
        }
        
        return (string) $department->getId();
    }

    /**
     * Verifica si el usuario actual es ADMIN (puede ver todo)
     */
    private function isAdmin(Security $security): bool
    {
        $user = $security->getUser();
        
        if (!$user instanceof User) {
            return false;
        }
        
        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    /**
     * Verifica si el usuario actual es MANAGER o ADMIN
     */
    private function isManagerOrAdmin(Security $security): bool
    {
        $user = $security->getUser();
        
        if (!$user instanceof User) {
            return false;
        }
        
        $roles = $user->getRoles();
        
        return in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_MANAGER', $roles, true);
    }

    /**
     * Verifica si el usuario tiene permisos de escritura (MANAGER o ADMIN)
     * Retorna JsonResponse de error si no tiene permisos
     */
    private function checkWritePermissions(): ?JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Usuario no autenticado'],
                Response::HTTP_UNAUTHORIZED
            );
        }
        
        // ADMIN tiene permisos totales
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return null;
        }
        
        // MANAGER tiene permisos limitados
        if (in_array('ROLE_MANAGER', $user->getRoles(), true)) {
            return null;
        }
        
        // Usuario normal no tiene permisos de escritura
        return new JsonResponse(
            ['error' => 'Acceso denegado. Se requieren permisos de MANAGER o ADMIN'],
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * Verifica si el usuario puede gestionar un departamento específico
     * Solo MANAGER y ADMIN pueden gestionar, y solo en su propio departamento (excepto ADMIN)
     */
    private function canManageDepartment(?Department $targetDepartment): ?JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Usuario no autenticado'],
                Response::HTTP_UNAUTHORIZED
            );
        }
        
        // ADMIN puede gestionar todo
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return null;
        }
        
        // MANAGER solo puede gestionar su propio departamento
        if (in_array('ROLE_MANAGER', $user->getRoles(), true)) {
            $userDepartment = $user->getDepartment();
            
            if (!$userDepartment) {
                return new JsonResponse(
                    ['error' => 'El usuario no pertenece a ningún departamento'],
                    Response::HTTP_FORBIDDEN
                );
            }
            
            if ($targetDepartment && $userDepartment !== $targetDepartment) {
                return new JsonResponse(
                    ['error' => 'Solo puedes gestionar elementos de tu propio departamento'],
                    Response::HTTP_FORBIDDEN
                );
            }
            
            return null;
        }
        
        // Usuario normal no tiene permisos
        return new JsonResponse(
            ['error' => 'Acceso denegado. Se requieren permisos de MANAGER o ADMIN'],
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * Verifica si el usuario puede gestionar un usuario específico
     */
    private function canManageUser(?User $targetUser): ?JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Usuario no autenticado'],
                Response::HTTP_UNAUTHORIZED
            );
        }
        
        // ADMIN puede gestionar todo
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return null;
        }
        
        // MANAGER solo puede gestionar usuarios de su departamento
        if (in_array('ROLE_MANAGER', $user->getRoles(), true)) {
            $userDepartment = $user->getDepartment();
            $targetDepartment = $targetUser?->getDepartment();
            
            if (!$userDepartment) {
                return new JsonResponse(
                    ['error' => 'El usuario no pertenece a ningún departamento'],
                    Response::HTTP_FORBIDDEN
                );
            }
            
            if (!$targetDepartment || $userDepartment !== $targetDepartment) {
                return new JsonResponse(
                    ['error' => 'Solo puedes gestionar usuarios de tu propio departamento'],
                    Response::HTTP_FORBIDDEN
                );
            }
            
            return null;
        }
        
        // Usuario normal no tiene permisos
        return new JsonResponse(
            ['error' => 'Acceso denegado. Se requieren permisos de MANAGER o ADMIN'],
            Response::HTTP_FORBIDDEN
        );
    }

    /**
     * Verifica si el usuario puede gestionar una guardia específica
     */
    private function canManageGuard($guard): ?JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return new JsonResponse(
                ['error' => 'Usuario no autenticado'],
                Response::HTTP_UNAUTHORIZED
            );
        }
        
        // ADMIN puede gestionar todo
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return null;
        }
        
        // MANAGER solo puede gestionar guardias de su departamento
        if (in_array('ROLE_MANAGER', $user->getRoles(), true)) {
            $userDepartment = $user->getDepartment();
            $guardDepartment = $guard?->getDepartment();
            
            if (!$userDepartment) {
                return new JsonResponse(
                    ['error' => 'El usuario no pertenece a ningún departamento'],
                    Response::HTTP_FORBIDDEN
                );
            }
            
            if (!$guardDepartment || $userDepartment !== $guardDepartment) {
                return new JsonResponse(
                    ['error' => 'Solo puedes gestionar guardias de tu propio departamento'],
                    Response::HTTP_FORBIDDEN
                );
            }
            
            return null;
        }
        
        // Usuario normal no tiene permisos
        return new JsonResponse(
            ['error' => 'Acceso denegado. Se requieren permisos de MANAGER o ADMIN'],
            Response::HTTP_FORBIDDEN
        );
    }
}
