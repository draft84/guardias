<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // El login se maneja automáticamente por lexik/jwt-authentication-bundle
        // Este endpoint solo sirve como fallback
        return new JsonResponse([
            'message' => 'Login successful',
        ], Response::HTTP_OK);
    }

    #[Route('/profile/change-password', name: 'api_auth_change_password', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function changePassword(Request $request, #[CurrentUser] UserInterface $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $currentPassword = $data['currentPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';
        $confirmPassword = $data['confirmPassword'] ?? '';

        // 1. Validar campos requeridos
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            return new JsonResponse([
                'error' => 'Todos los campos son obligatorios',
            ], Response::HTTP_BAD_REQUEST);
        }

        // 2. Validar contraseña actual
        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            return new JsonResponse([
                'error' => 'La contraseña actual no es correcta',
            ], Response::HTTP_BAD_REQUEST);
        }

        // 3. Validar longitud mínima
        if (strlen($newPassword) < 6) {
            return new JsonResponse([
                'error' => 'La nueva contraseña debe tener al menos 6 caracteres',
            ], Response::HTTP_BAD_REQUEST);
        }

        // 4. Validar coincidencia
        if ($newPassword !== $confirmPassword) {
            return new JsonResponse([
                'error' => 'La confirmación de contraseña no coincide',
            ], Response::HTTP_BAD_REQUEST);
        }

        // 5. Actualizar
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Contraseña actualizada correctamente',
        ], Response::HTTP_OK);
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?UserInterface $user): JsonResponse
    {
        if (!$user instanceof UserInterface) {
            return new JsonResponse([
                'error' => 'User not authenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user' => [
                'id' => method_exists($user, 'getId') ? (string) $user->getId() : null,
                'email' => method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : null,
                'firstName' => method_exists($user, 'getFirstName') ? $user->getFirstName() : null,
                'lastName' => method_exists($user, 'getLastName') ? $user->getLastName() : null,
                'fullName' => method_exists($user, 'getFullName') ? $user->getFullName() : null,
                'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : [],
                'department' => method_exists($user, 'getDepartment') && $user->getDepartment() ? (string) $user->getDepartment()->getId() : null,
                'departmentName' => method_exists($user, 'getDepartment') && $user->getDepartment() ? $user->getDepartment()->getName() : null,
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        // El logout se maneja invalidando el token JWT en el cliente
        return new JsonResponse([
            'message' => 'Logout successful',
        ], Response::HTTP_OK);
    }

    #[Route('/refresh', name: 'api_auth_refresh', methods: ['POST'])]
    public function refresh(): JsonResponse
    {
        // El refresh se maneja automáticamente por lexik/jwt-authentication-bundle
        return new JsonResponse([
            'message' => 'Token refreshed',
        ], Response::HTTP_OK);
    }
}
