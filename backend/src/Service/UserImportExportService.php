<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Department;
use App\Entity\GuardLevel;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use libphonenumber\PhoneNumberUtil;

class UserImportExportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private PhoneNumberUtil $phoneNumberUtil
    ) {}

    /**
     * Importa usuarios desde un archivo Excel
     * 
     * El archivo debe tener las siguientes columnas:
     * - email (requerido)
     * - password (requerido)
     * - firstName (requerido)
     * - lastName (requerido)
     * - phone (opcional)
     * - departmentCode (requerido) - Código del departamento
     * - guardLevelName (opcional) - Nombre del nivel
     * - roles (opcional) - Separados por coma, por defecto ROLE_USER
     * - active (opcional) - 1 o 0, por defecto 1
     * 
     * @return array ['success' => int, 'errors' => array]
     */
    public function importUsers(UploadedFile $file, string $userDepartmentId): array
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
                $rowNumber = $index + 2; // +2 porque saltamos la primera fila y los índices empiezan en 0

                // Validar campos requeridos
                if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4])) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => 'Campos requeridos vacíos (email, password, firstName, lastName, departmentCode)'
                    ];
                    continue;
                }

                try {
                    // Buscar departamento por código
                    $departmentCode = trim($row[4]);
                    $department = $this->entityManager->getRepository(Department::class)->findOneBy(['code' => $departmentCode]);

                    if (!$department) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'error' => "Departamento no encontrado: {$departmentCode}"
                        ];
                        continue;
                    }

                    // Verificar que el email no exista
                    $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => trim($row[0])]);
                    if ($existingUser) {
                        $errors[] = [
                            'row' => $rowNumber,
                            'error' => "El email " . trim($row[0]) . " ya está en uso"
                        ];
                        continue;
                    }

                    // Crear usuario
                    $user = new User();
                    $user->setEmail(trim($row[0]));
                    $user->setFirstName(trim($row[2]));
                    $user->setLastName(trim($row[3]));
                    $user->setDepartment($department);

                    // Password
                    $hashedPassword = $this->passwordHasher->hashPassword($user, trim($row[1]));
                    $user->setPassword($hashedPassword);

                    // Teléfono (opcional)
                    if (!empty($row[5])) {
                        try {
                            $phoneNumber = $this->phoneNumberUtil->parse(trim($row[5]), 'VE');
                            $user->setPhone($phoneNumber);
                        } catch (\Exception $e) {
                            // Si el teléfono no es válido, lo ignoramos
                        }
                    }

                    // Nivel de guardia (opcional)
                    if (!empty($row[6])) {
                        $guardLevel = $this->entityManager->getRepository(GuardLevel::class)->findOneBy(['name' => trim($row[6])]);
                        if ($guardLevel) {
                            $user->setGuardLevel($guardLevel);
                        }
                    }

                    // Roles (opcional)
                    if (!empty($row[7])) {
                        $roles = array_map('trim', explode(',', $row[7]));
                        $user->setRoles($roles);
                    } else {
                        $user->setRoles(['ROLE_USER']);
                    }

                    // Activo (opcional, por defecto 1)
                    $active = !empty($row[8]) ? (int)$row[8] : 1;
                    $user->setActive($active === 1);

                    $this->entityManager->persist($user);
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

    /**
     * Exporta usuarios a un archivo Excel
     * 
     * @param User[] $users
     * @return string Contenido del archivo Excel
     */
    public function exportUsers(array $users): string
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // Encabezados
        $headers = [
            'Email',
            'Password',
            'FirstName',
            'LastName',
            'Phone',
            'Department Code',
            'Guard Level',
            'Roles',
            'Active'
        ];

        $worksheet->fromArray([$headers], null, 'A1');

        // Estilos para encabezados
        $worksheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'CCCCCC']
            ]
        ]);

        // Datos
        $rowIndex = 2;
        foreach ($users as $user) {
            $worksheet->setCellValue('A' . $rowIndex, $user->getEmail());
            $worksheet->setCellValue('B' . $rowIndex, '[PROTECTED]'); // No exportamos passwords reales
            $worksheet->setCellValue('C' . $rowIndex, $user->getFirstName());
            $worksheet->setCellValue('D' . $rowIndex, $user->getLastName());
            $worksheet->setCellValue('E' . $rowIndex, $user->getPhone() ?? '');
            $worksheet->setCellValue('F' . $rowIndex, $user->getDepartment()?->getCode() ?? '');
            $worksheet->setCellValue('G' . $rowIndex, $user->getGuardLevel()?->getName() ?? '');
            $worksheet->setCellValue('H' . $rowIndex, implode(', ', $user->getRoles()));
            $worksheet->setCellValue('I' . $rowIndex, $user->isActive() ? 1 : 0);

            $rowIndex++;
        }

        // Auto-size para columnas
        foreach (range('A', 'I') as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Guardar en memoria
        $writer = new Xlsx($spreadsheet);
        
        // Usar un stream temporal
        $tempFile = tempnam(sys_get_temp_dir(), 'users_export_');
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return $content;
    }

    /**
     * Crea una plantilla Excel para la carga de usuarios
     * 
     * @return string Contenido del archivo Excel
     */
    public function createTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        // Encabezados
        $headers = [
            'Email *',
            'Password *',
            'FirstName *',
            'LastName *',
            'Department Code *',
            'Phone',
            'Guard Level',
            'Roles',
            'Active'
        ];

        $worksheet->fromArray([$headers], null, 'A1');

        // Estilos para encabezados
        $worksheet->getStyle('A1:I1')->applyFromArray([
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
            [
                'usuario1@example.com',
                'password123',
                'Juan',
                'Pérez',
                'OPS',
                '04121234567',
                'Junior',
                'ROLE_USER',
                1
            ],
            [
                'usuario2@example.com',
                'password123',
                'María',
                'García',
                'TECH',
                '04147654321',
                'Senior',
                'ROLE_USER',
                1
            ]
        ];

        $worksheet->fromArray($exampleData, null, 'A2');

        // Auto-size para columnas
        foreach (range('A', 'I') as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Congelar primera fila
        $worksheet->freezePane('A2');

        // Hoja de referencia
        $spreadsheet->createSheet();
        $infoSheet = $spreadsheet->getSheet(1);
        $infoSheet->setTitle('Instrucciones');

        $instructions = [
            ['INSTRUCCIONES PARA CARGA MASIVA DE USUARIOS'],
            [''],
            ['Campos requeridos (marcados con *):'],
            ['  - Email: Debe ser único y válido'],
            ['  - Password: Contraseña del usuario (mínimo 6 caracteres)'],
            ['  - FirstName: Nombre del usuario'],
            ['  - LastName: Apellido del usuario'],
            ['  - Department Code: Código del departamento (ej: OPS, TECH, HR)'],
            [''],
            ['Campos opcionales:'],
            ['  - Phone: Número de teléfono'],
            ['  - Guard Level: Nombre del nivel (ej: Junior, Senior, Residente)'],
            ['  - Roles: Separados por coma (ej: ROLE_USER, ROLE_MANAGER). Por defecto: ROLE_USER'],
            ['  - Active: 1 para activo, 0 para inactivo. Por defecto: 1'],
            [''],
            ['Departamentos disponibles:'],
            ['  - OPS: Operaciones'],
            ['  - TECH: Tecnología'],
            ['  - HR: Recursos Humanos'],
            [''],
            ['Ejemplos:'],
            ['  Email: juan.perez@example.com'],
            ['  Password: miPassword123'],
            ['  Department Code: OPS'],
            ['  Roles: ROLE_USER']
        ];

        $infoSheet->fromArray($instructions, null, 'A1');
        $infoSheet->getColumnDimension('A')->setWidth(60);

        // Guardar en memoria
        $writer = new Xlsx($spreadsheet);
        
        // Usar un stream temporal
        $tempFile = tempnam(sys_get_temp_dir(), 'users_template_');
        $writer->save($tempFile);
        $content = file_get_contents($tempFile);
        unlink($tempFile);

        return $content;
    }
}
