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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    #[Route('/export', name: 'api_guards_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }
        
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        
        // Obtener filtros
        $departmentId = $request->query->get('department');
        
        // Obtener todas las guardias
        $guards = $this->entityManager->getRepository(Guard::class)->findAll();
        
        // Filtrar por departamento
        if (!$isAdmin && $user->getDepartment()) {
            $guards = array_filter($guards, fn($g) => 
                $g->getDepartment() && $g->getDepartment()->getId() === $user->getDepartment()->getId()
            );
        } elseif ($departmentId) {
            $guards = array_filter($guards, fn($g) => 
                $g->getDepartment() && (string) $g->getDepartment()->getId() === $departmentId
            );
        }
        
        // Crear Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Guardias');
        
        // Estilos
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        
        $borderStyle = [
            'borders' => [
                'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']],
            ],
        ];
        
        // Headers
        $headers = [
            'Guardia',
            'Usuario Asignado',
            'Email',
            'Teléfono',
            'Departamento',
            'Fecha',
            'Hora Inicio',
            'Hora Fin',
            'Duración (horas)'
        ];
        
        $sheet->fromArray([$headers], null, 'A1');
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        
        // Datos
        $row = 2;
        foreach ($guards as $guard) {
            $assignments = $guard->getAssignments();
            
            // Si no tiene asignaciones, mostrar la guardia sin usuario
            if ($assignments->isEmpty()) {
                $sheet->fromArray([[
                    $guard->getName(),
                    '',
                    '',
                    '',
                    $guard->getDepartment()?->getName() ?? '',
                    '',
                    $guard->getStartTime()?->format('H:i'),
                    $guard->getEndTime()?->format('H:i'),
                    $guard->getDuration() ? round($guard->getDuration() / 60, 2) : '',
                ]], null, "A{$row}");
                $row++;
            } else {
                foreach ($assignments as $assignment) {
                    $userAssigned = $assignment->getUser();
                    $startTime = $assignment->getStartTime();
                    $endTime = $assignment->getEndTime();
                    $durationMinutes = $startTime && $endTime ? 
                        ($endTime->getTimestamp() - $startTime->getTimestamp()) / 60 : 0;
                    
                    // Formato seguro de teléfono
                    $phone = '';
                    if ($userAssigned && $userAssigned->getPhone()) {
                        try {
                            $phone = '+' . $userAssigned->getPhone()->getCountryCode() . ' ' . $userAssigned->getPhone()->getNationalNumber();
                        } catch (\Exception $e) {
                            $phone = $userAssigned->getPhone();
                        }
                    }
                    
                    $sheet->fromArray([[
                        $guard->getName(),
                        $userAssigned?->getFullName() ?? '',
                        $userAssigned?->getEmail() ?? '',
                        $phone,
                        $guard->getDepartment()?->getName() ?? '',
                        $assignment->getDate()?->format('Y-m-d'),
                        $startTime?->format('H:i'),
                        $endTime?->format('H:i'),
                        $durationMinutes > 0 ? round($durationMinutes / 60, 2) : '',
                    ]], null, "A{$row}");
                    $row++;
                }
            }
        }
        
        // Aplicar bordes
        $lastRow = $row - 1;
        $sheet->getStyle("A1:I{$lastRow}")->applyFromArray($borderStyle);
        
        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Descargar
        $filename = 'guardias_' . date('Y-m-d_His') . '.xlsx';
        
        // Headers CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
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
    public function delete(Guard $guard, GuardService $guardService): JsonResponse
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

        // Usar el servicio para eliminar la guardia (incluye eliminar notificaciones relacionadas)
        $guardService->deleteGuard($guard);

        $response = new JsonResponse([
            'message' => 'Guard deleted successfully',
        ], Response::HTTP_OK);
        
        // Header para notificar al frontend que actualice
        $response->headers->set('X-Notifications-Updated', 'true');
        
        return $response;
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
