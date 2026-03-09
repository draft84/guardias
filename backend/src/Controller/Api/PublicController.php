<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\GuardAssignmentRepository;
use App\Repository\DepartmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/public')]
class PublicController extends AbstractController
{
    public function __construct(
        private GuardAssignmentRepository $assignmentRepository,
        private DepartmentRepository $departmentRepository
    ) {}

    #[Route('/today-guards', name: 'api_public_today_guards', methods: ['GET'])]
    public function todayGuards(): JsonResponse
    {
        $today = new \DateTime();
        $assignments = $this->assignmentRepository->findByDate($today);
        
        $departments = $this->departmentRepository->findBy(['active' => true]);
        
        $groupedData = [];
        
        foreach ($departments as $dept) {
            $deptId = (string) $dept->getId();
            $deptAssignments = array_filter($assignments, function($a) use ($dept) {
                return $a->getGuard()->getDepartment() === $dept;
            });
            
            if (empty($deptAssignments)) {
                continue;
            }

            $groupedData[] = [
                'departmentId' => $deptId,
                'departmentName' => $dept->getName(),
                'guards' => array_map(function($a) {
                    return [
                        'id' => (string) $a->getId(),
                        'guardName' => $a->getGuard()->getName(),
                        'startTime' => $a->getStartTime()->format('H:i'),
                        'endTime' => $a->getEndTime()->format('H:i'),
                        'userName' => $a->getUser()->getFullName(),
                        'userLevel' => $a->getUser()->getGuardLevel()?->getName(),
                        'status' => $a->getStatus(),
                    ];
                }, array_values($deptAssignments))
            ];
        }

        return new JsonResponse([
            'date' => $today->format('Y-m-d'),
            'data' => $groupedData
        ], Response::HTTP_OK);
    }
}
