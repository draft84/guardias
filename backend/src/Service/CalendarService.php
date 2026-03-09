<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\GuardAssignmentRepository;
use App\Repository\GuardRepository;

class CalendarService
{
    public function __construct(
        private GuardAssignmentRepository $assignmentRepository,
        private GuardRepository $guardRepository
    ) {}

    /**
     * Get calendar events for a specific month
     */
    public function getCalendarEvents(int $month, int $year, ?string $departmentId = null): array
    {
        $startDate = \DateTime::createFromFormat('Y-n-j', "$year-$month-1");
        $endDate = clone $startDate;
        $endDate->modify('last day of this month');
        
        $allAssignments = $this->assignmentRepository->findAll();
        $events = [];

        foreach ($allAssignments as $assignment) {
            $assignDate = $assignment->getDate();
            
            if (!$assignDate) {
                continue;
            }
            
            // Filter by date range
            if ($assignDate < $startDate || $assignDate > $endDate) {
                continue;
            }

            // Filter by department if specified
            if ($departmentId !== null) {
                $guardDepartment = $assignment->getGuard()->getDepartment();
                if ($guardDepartment === null || (string) $guardDepartment->getId() !== $departmentId) {
                    continue;
                }
            }

            $events[] = [
                'id' => (string) $assignment->getId(),
                'title' => sprintf(
                    '%s - %s',
                    $assignment->getGuard()->getName(),
                    $assignment->getUser()->getFullName()
                ),
                'start' => sprintf(
                    '%s %s',
                    $assignDate->format('Y-m-d'),
                    $assignment->getStartTime()?->format('H:i:s') ?? '00:00:00'
                ),
                'end' => sprintf(
                    '%s %s',
                    $assignDate->format('Y-m-d'),
                    $assignment->getEndTime()?->format('H:i:s') ?? '23:59:59'
                ),
                'status' => $assignment->getStatus(),
                'guard' => [
                    'id' => (string) $assignment->getGuard()->getId(),
                    'name' => $assignment->getGuard()->getName(),
                    'code' => $assignment->getGuard()->getCode(),
                ],
                'user' => [
                    'id' => (string) $assignment->getUser()->getId(),
                    'fullName' => $assignment->getUser()->getFullName(),
                    'email' => $assignment->getUser()->getEmail(),
                ],
                'allDay' => false,
            ];
        }

        return $events;
    }

    /**
     * Get guards for a specific date
     */
    public function getGuardsByDate(\DateTimeInterface $date, ?string $departmentId = null): array
    {
        $assignments = $this->assignmentRepository->findByDate($date);
        $guards = [];

        foreach ($assignments as $assignment) {
            // Filter by department if specified
            if ($departmentId !== null) {
                $guardDepartment = $assignment->getGuard()->getDepartment();
                if ($guardDepartment === null || (string) $guardDepartment->getId() !== $departmentId) {
                    continue;
                }
            }

            $guards[] = [
                'id' => (string) $assignment->getId(),
                'guard' => [
                    'id' => (string) $assignment->getGuard()->getId(),
                    'name' => $assignment->getGuard()->getName(),
                    'code' => $assignment->getGuard()->getCode(),
                ],
                'user' => [
                    'id' => (string) $assignment->getUser()->getId(),
                    'fullName' => $assignment->getUser()->getFullName(),
                    'email' => $assignment->getUser()->getEmail(),
                ],
                'startTime' => $assignment->getStartTime()?->format('H:i'),
                'endTime' => $assignment->getEndTime()?->format('H:i'),
                'status' => $assignment->getStatus(),
                'notes' => $assignment->getNotes(),
            ];
        }

        return $guards;
    }

    /**
     * Get user's guards for a date range
     */
    public function getUserGuards(
        string $userId,
        \DateTimeInterface $start,
        \DateTimeInterface $end
    ): array {
        $assignments = $this->assignmentRepository->findByUserAndDateRange($userId, $start, $end);
        $guards = [];

        foreach ($assignments as $assignment) {
            $guards[] = [
                'id' => (string) $assignment->getId(),
                'date' => $assignment->getDate()?->format('Y-m-d'),
                'guard' => [
                    'id' => (string) $assignment->getGuard()->getId(),
                    'name' => $assignment->getGuard()->getName(),
                    'code' => $assignment->getGuard()->getCode(),
                ],
                'startTime' => $assignment->getStartTime()?->format('H:i'),
                'endTime' => $assignment->getEndTime()?->format('H:i'),
                'status' => $assignment->getStatus(),
            ];
        }

        return $guards;
    }

    /**
     * Get active guards at a specific datetime
     */
    public function getActiveGuardsAtTime(\DateTimeInterface $datetime): array
    {
        $allAssignments = $this->assignmentRepository->findAll();
        $activeGuards = [];

        foreach ($allAssignments as $assignment) {
            if ($assignment->getStatus() !== 'active' && $assignment->getStatus() !== 'scheduled') {
                continue;
            }

            $assignDate = $assignment->getDate();
            $startTime = $assignment->getStartTime();
            $endTime = $assignment->getEndTime();

            if (!$assignDate || !$startTime || !$endTime) {
                continue;
            }

            // Create datetime for comparison
            $assignmentStart = \DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $assignDate->format('Y-m-d') . ' ' . $startTime->format('H:i:s')
            );
            $assignmentEnd = \DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $assignDate->format('Y-m-d') . ' ' . $endTime->format('H:i:s')
            );

            if ($datetime >= $assignmentStart && $datetime <= $assignmentEnd) {
                $activeGuards[] = [
                    'id' => (string) $assignment->getId(),
                    'guard' => [
                        'id' => (string) $assignment->getGuard()->getId(),
                        'name' => $assignment->getGuard()->getName(),
                    ],
                    'user' => [
                        'id' => (string) $assignment->getUser()->getId(),
                        'fullName' => $assignment->getUser()->getFullName(),
                    ],
                    'startTime' => $startTime->format('H:i'),
                    'endTime' => $endTime->format('H:i'),
                    'status' => $assignment->getStatus(),
                ];
            }
        }

        return $activeGuards;
    }

    /**
     * Get guards count by day for a month
     */
    public function getGuardsCountByDay(int $month, int $year): array
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $counts = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = \DateTime::createFromFormat('Y-n-j', "$year-$month-$day");
            $assignments = $this->assignmentRepository->findByDate($date);
            
            $counts[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $day,
                'count' => count($assignments),
                'hasGuards' => count($assignments) > 0,
            ];
        }

        return $counts;
    }

    /**
     * Get statistics for a month
     */
    public function getMonthStatistics(int $month, int $year): array
    {
        $events = $this->getCalendarEvents($month, $year);
        
        $totalGuards = count($events);
        $completedGuards = 0;
        $scheduledGuards = 0;
        $cancelledGuards = 0;
        
        $usersCount = [];
        
        foreach ($events as $event) {
            switch ($event['status']) {
                case 'completed':
                    $completedGuards++;
                    break;
                case 'scheduled':
                    $scheduledGuards++;
                    break;
                case 'cancelled':
                    $cancelledGuards++;
                    break;
            }
            
            $userId = $event['user']['id'];
            if (!isset($usersCount[$userId])) {
                $usersCount[$userId] = [
                    'userId' => $userId,
                    'userName' => $event['user']['fullName'],
                    'count' => 0,
                ];
            }
            $usersCount[$userId]['count']++;
        }

        return [
            'month' => $month,
            'year' => $year,
            'totalGuards' => $totalGuards,
            'completedGuards' => $completedGuards,
            'scheduledGuards' => $scheduledGuards,
            'cancelledGuards' => $cancelledGuards,
            'users' => array_values($usersCount),
        ];
    }
}
