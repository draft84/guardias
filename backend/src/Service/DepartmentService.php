<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Department;
use App\Entity\User;
use App\Repository\DepartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DepartmentService
{
    public function __construct(
        private DepartmentRepository $departmentRepository,
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    /**
     * @return Department[]
     */
    public function getAllDepartments(): array
    {
        $user = $this->security->getUser();
        
        // ADMIN puede ver todos los departamentos
        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->departmentRepository->findAll();
        }
        
        // Los demás solo ven su propio departamento
        $userDepartment = $user?->getDepartment();
        if (!$userDepartment) {
            return [];
        }
        
        return [$userDepartment];
    }

    /**
     * @return Department[]
     */
    public function getActiveDepartments(): array
    {
        $user = $this->security->getUser();
        
        // ADMIN puede ver todos los departamentos activos
        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->departmentRepository->findActiveDepartments();
        }
        
        // Los demás solo ven su propio departamento si está activo
        $userDepartment = $user?->getDepartment();
        if (!$userDepartment || !$userDepartment->isActive()) {
            return [];
        }
        
        return [$userDepartment];
    }

    public function getDepartmentById(string $id): ?Department
    {
        $department = $this->departmentRepository->find($id);
        
        if (!$department) {
            return null;
        }
        
        // Verificar permisos por departamento
        $user = $this->security->getUser();
        
        // ADMIN puede ver todos
        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $department;
        }
        
        // Los demás solo pueden ver su propio departamento
        $userDepartment = $user?->getDepartment();
        
        if (!$userDepartment || $userDepartment !== $department) {
            return null;
        }
        
        return $department;
    }

    public function createDepartment(
        string $name,
        string $code,
        ?string $description = null,
        ?string $parentDepartmentId = null,
        bool $active = true
    ): Department {
        $department = new Department();
        $department->setName($name);
        $department->setCode($code);
        $department->setDescription($description);
        $department->setActive($active);

        $this->entityManager->persist($department);
        $this->entityManager->flush();

        return $department;
    }

    public function updateDepartment(
        Department $department,
        ?string $name = null,
        ?string $code = null,
        ?string $description = null,
        ?bool $active = null,
        ?string $parentDepartmentId = null
    ): Department {
        if ($name !== null) {
            $department->setName($name);
        }
        if ($code !== null) {
            $department->setCode($code);
        }
        if ($description !== null) {
            $department->setDescription($description);
        }
        if ($active !== null) {
            $department->setActive($active);
        }

        $this->entityManager->flush();

        return $department;
    }

    public function deleteDepartment(Department $department): void
    {
        $this->entityManager->remove($department);
        $this->entityManager->flush();
    }

    /**
     * @return Department[]
     */
    public function getChildDepartments(Department $department): array
    {
        return $department->getChildren()->toArray();
    }

    public function setParentDepartment(Department $department, ?Department $parent): Department
    {
        $department->setParentDepartment($parent);
        $this->entityManager->flush();

        return $department;
    }

    /**
     * Get department hierarchy as a tree
     */
    public function getDepartmentTree(?Department $parent = null): array
    {
        $departments = $this->getAllDepartments();
        $tree = [];

        foreach ($departments as $dept) {
            if ($parent === null && $dept->getParentDepartment() === null) {
                $tree[] = $this->buildDepartmentTree($dept, $departments);
            } elseif ($parent !== null && $dept->getParentDepartment() === $parent) {
                $tree[] = $this->buildDepartmentTree($dept, $departments);
            }
        }

        return $tree;
    }

    private function buildDepartmentTree(Department $department, array $allDepartments): array
    {
        $node = [
            'id' => (string) $department->getId(),
            'name' => $department->getName(),
            'code' => $department->getCode(),
            'children' => [],
        ];

        foreach ($allDepartments as $dept) {
            if ($dept->getParentDepartment() === $department) {
                $node['children'][] = $this->buildDepartmentTree($dept, $allDepartments);
            }
        }

        return $node;
    }

    /**
     * Exporta departamentos a un archivo Excel
     *
     * @return string Contenido del archivo Excel
     */
    public function exportDepartments(): string
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // Encabezados
        $headers = [
            'Name *',
            'Code *',
            'Description',
            'Active'
        ];

        $worksheet->fromArray([$headers], null, 'A1');

        // Estilos para encabezados
        $worksheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50']
            ]
        ]);

        // Datos
        $departments = $this->getAllDepartments();
        $rowIndex = 2;
        foreach ($departments as $dept) {
            $worksheet->setCellValue('A' . $rowIndex, $dept->getName());
            $worksheet->setCellValue('B' . $rowIndex, $dept->getCode());
            $worksheet->setCellValue('C' . $rowIndex, $dept->getDescription() ?? '');
            $worksheet->setCellValue('D' . $rowIndex, $dept->isActive() ? 1 : 0);
            $rowIndex++;
        }

        // Auto-size para columnas
        foreach (range('A', 'D') as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Guardar en memoria
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'dept_export_');
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return $content;
    }

    /**
     * Crea una plantilla Excel para la carga de departamentos
     *
     * @return string Contenido del archivo Excel
     */
    public function createTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // Encabezados
        $headers = [
            'Name *',
            'Code *',
            'Description',
            'Active'
        ];

        $worksheet->fromArray([$headers], null, 'A1');

        // Estilos para encabezados
        $worksheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50']
            ]
        ]);

        // Ejemplo de datos
        $exampleData = [
            ['Tecnología', 'TECH', 'Departamento de tecnología', 1],
            ['Recursos Humanos', 'HR', 'Departamento de recursos humanos', 1],
            ['Operaciones', 'OPS', 'Departamento de operaciones', 1]
        ];

        $worksheet->fromArray($exampleData, null, 'A2');

        // Auto-size para columnas
        foreach (range('A', 'D') as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Congelar primera fila
        $worksheet->freezePane('A2');

        // Hoja de instrucciones
        $spreadsheet->createSheet();
        $infoSheet = $spreadsheet->getSheet(1);
        $infoSheet->setTitle('Instrucciones');

        $instructions = [
            ['INSTRUCCIONES PARA CARGA MASIVA DE DEPARTAMENTOS'],
            [''],
            ['Campos requeridos (marcados con *):'],
            ['  - Name: Nombre del departamento (debe ser único)'],
            ['  - Code: Código corto (debe ser único, ej: TECH, HR, OPS)'],
            [''],
            ['Campos opcionales:'],
            ['  - Description: Descripción del departamento'],
            ['  - Active: 1 para activo, 0 para inactivo. Por defecto: 1'],
            [''],
            ['Ejemplos:'],
            ['  Name: Tecnología'],
            ['  Code: TECH'],
            ['  Active: 1']
        ];

        $infoSheet->fromArray($instructions, null, 'A1');
        $infoSheet->getColumnDimension('A')->setWidth(60);

        // Guardar en memoria
        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'dept_template_');
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return $content;
    }

    /**
     * Importa departamentos desde un archivo Excel
     *
     * @return array ['success' => int, 'errors' => array, 'total' => int]
     */
    public function importDepartments(UploadedFile $file): array
    {
        try {
            // Verificar que el archivo existe
            if (!$file->isValid()) {
                return [
                    'success' => 0,
                    'errors' => [['row' => 0, 'error' => 'El archivo no es válido']],
                    'total' => 0
                ];
            }

            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $success = 0;
            $errors = [];

            // Saltar la primera fila (encabezados)
            array_shift($rows);

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                // Validar campos requeridos
                if (empty($row[0]) || empty($row[1])) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => 'Campos requeridos vacíos (Name, Code)'
                    ];
                    continue;
                }

                try {
                    // Verificar que el código no exista
                    $existingDept = $this->departmentRepository->findOneBy(['code' => trim($row[1])]);
                    if ($existingDept) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'error' => "El código " . trim($row[1]) . " ya está en uso"
                        ];
                        continue;
                    }

                    // Crear departamento
                    $department = new Department();
                    $department->setName(trim($row[0]));
                    $department->setCode(trim($row[1]));
                    $department->setDescription(!empty($row[2]) ? trim($row[2]) : null);

                    // Activo (opcional, por defecto 1)
                    $active = !empty($row[3]) ? (int)$row[3] : 1;
                    $department->setActive($active === 1);

                    $this->entityManager->persist($department);
                    $success++;

                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => $e->getMessage()
                    ];
                }
            }

            $this->entityManager->flush();

        } catch (\Exception $e) {
            return [
                'success' => 0,
                'errors' => [['row' => 0, 'error' => 'Error al procesar el archivo: ' . $e->getMessage()]],
                'total' => 0
            ];
        }

        return [
            'success' => $success,
            'errors' => $errors,
            'total' => count($rows)
        ];
    }
}
