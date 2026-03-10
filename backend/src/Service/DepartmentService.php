<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Department;
use App\Entity\User;
use App\Repository\DepartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

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
}
