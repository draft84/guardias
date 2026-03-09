<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Shift;
use App\Repository\ShiftRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/shifts')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ShiftController extends AbstractController
{
    public function __construct(
        private ShiftRepository $shiftRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'api_shifts_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $shifts = $this->shiftRepository->findAll();

        $data = array_map(function (Shift $shift) {
            return [
                'id' => (string) $shift->getId(),
                'name' => $shift->getName(),
                'code' => $shift->getCode(),
                'startTime' => $shift->getStartTime()?->format('H:i'),
                'endTime' => $shift->getEndTime()?->format('H:i'),
                'type' => $shift->getType(),
                'color' => $shift->getColor(),
                'active' => $shift->isActive(),
                'createdAt' => $shift->getCreatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $shifts);

        return new JsonResponse(['shifts' => $data], Response::HTTP_OK);
    }

    #[Route('/active', name: 'api_shifts_active', methods: ['GET'])]
    public function listActive(): JsonResponse
    {
        $shifts = $this->shiftRepository->findActiveShifts();
        
        $data = array_map(function (Shift $shift) {
            return [
                'id' => (string) $shift->getId(),
                'name' => $shift->getName(),
                'code' => $shift->getCode(),
                'startTime' => $shift->getStartTime()?->format('H:i'),
                'endTime' => $shift->getEndTime()?->format('H:i'),
                'type' => $shift->getType(),
                'color' => $shift->getColor(),
                'active' => $shift->isActive(),
            ];
        }, $shifts);

        return new JsonResponse(['shifts' => $data], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_shifts_get', methods: ['GET'])]
    public function get(Shift $shift): JsonResponse
    {
        $data = [
            'id' => (string) $shift->getId(),
            'name' => $shift->getName(),
            'code' => $shift->getCode(),
            'startTime' => $shift->getStartTime()?->format('H:i'),
            'endTime' => $shift->getEndTime()?->format('H:i'),
            'type' => $shift->getType(),
            'color' => $shift->getColor(),
            'active' => $shift->isActive(),
        ];

        return new JsonResponse(['shift' => $data], Response::HTTP_OK);
    }

    #[Route('', name: 'api_shifts_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['name']) || empty($data['code']) || 
            empty($data['startTime']) || empty($data['endTime'])) {
            return new JsonResponse([
                'error' => 'Name, code, startTime and endTime are required',
            ], Response::HTTP_BAD_REQUEST);
        }

        $shift = new Shift();
        $shift->setName($data['name']);
        $shift->setCode($data['code']);
        $shift->setStartTime(new \DateTime($data['startTime']));
        $shift->setEndTime(new \DateTime($data['endTime']));
        $shift->setType($data['type'] ?? 'custom');
        $shift->setColor($data['color'] ?? '#3498db');
        $shift->setActive($data['active'] ?? true);

        $this->entityManager->persist($shift);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Shift created successfully',
            'shift' => [
                'id' => (string) $shift->getId(),
                'name' => $shift->getName(),
                'code' => $shift->getCode(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_shifts_update', methods: ['PUT'])]
    public function update(Request $request, Shift $shift): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $shift->setName($data['name']);
        }
        if (isset($data['code'])) {
            $shift->setCode($data['code']);
        }
        if (isset($data['startTime'])) {
            $shift->setStartTime(new \DateTime($data['startTime']));
        }
        if (isset($data['endTime'])) {
            $shift->setEndTime(new \DateTime($data['endTime']));
        }
        if (isset($data['type'])) {
            $shift->setType($data['type']);
        }
        if (isset($data['color'])) {
            $shift->setColor($data['color']);
        }
        if (isset($data['active'])) {
            $shift->setActive($data['active']);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Shift updated successfully',
            'shift' => [
                'id' => (string) $shift->getId(),
                'name' => $shift->getName(),
                'code' => $shift->getCode(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_shifts_delete', methods: ['DELETE'])]
    public function delete(Shift $shift): JsonResponse
    {
        $this->entityManager->remove($shift);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Shift deleted successfully',
        ], Response::HTTP_OK);
    }
}
