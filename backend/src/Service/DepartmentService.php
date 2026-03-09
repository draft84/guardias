<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Department;
use App\Repository\DepartmentRepository;
use Doctrine\ORM\EntityManagerInterface;

class DepartmentService
{
    public function __construct(
        private DepartmentRepository $departmentRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * @return Department[]
     */
    public function getAllDepartments(): array
    {
        return $this->departmentRepository->findAll();
    }

    /**
     * @return Department[]
     */
    public function getActiveDepartments(): array
    {
        return $this->departmentRepository->findActiveDepartments();
    }

    public function getDepartmentById(string $id): ?Department
    {
        return $this->departmentRepository->find($id);
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
