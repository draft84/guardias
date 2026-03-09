<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\GuardLevel;
use App\Repository\GuardLevelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/guard-levels')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class GuardLevelController extends AbstractController
{
    public function __construct(
        private GuardLevelRepository $guardLevelRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'api_guard_levels_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $levels = $this->guardLevelRepository->findAll();

        $data = array_map(function (GuardLevel $level) {
            return [
                'id' => (string) $level->getId(),
                'name' => $level->getName(),
                'isUsed' => $level->isUsed(),
            ];
        }, $levels);

        return new JsonResponse(['levels' => $data], Response::HTTP_OK);
    }

    #[Route('', name: 'api_guard_levels_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return new JsonResponse([
                'error' => 'Name is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if name already exists
        $existing = $this->guardLevelRepository->findOneBy(['name' => $data['name']]);
        if ($existing) {
            return new JsonResponse([
                'error' => 'A level with this name already exists',
            ], Response::HTTP_CONFLICT);
        }

        $level = new GuardLevel();
        $level->setName($data['name']);

        $this->entityManager->persist($level);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Guard level created successfully',
            'level' => [
                'id' => (string) $level->getId(),
                'name' => $level->getName(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_guard_levels_update', methods: ['PUT'])]
    public function update(GuardLevel $level, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return new JsonResponse([
                'error' => 'Name is required',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Check if name already exists in another level
        $existing = $this->guardLevelRepository->findOneBy(['name' => $data['name']]);
        if ($existing && $existing->getId() !== $level->getId()) {
            return new JsonResponse([
                'error' => 'A level with this name already exists',
            ], Response::HTTP_CONFLICT);
        }

        $level->setName($data['name']);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Guard level updated successfully',
            'level' => [
                'id' => (string) $level->getId(),
                'name' => $level->getName(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_guard_levels_delete', methods: ['DELETE'])]
    public function delete(GuardLevel $level): JsonResponse
    {
        if ($level->isUsed()) {
            return new JsonResponse([
                'error' => 'No se puede eliminar el nivel porque está asignado a una o más guardias.',
            ], Response::HTTP_CONFLICT);
        }

        $this->entityManager->remove($level);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Guard level deleted successfully',
        ], Response::HTTP_OK);
    }
}
