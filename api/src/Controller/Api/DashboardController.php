<?php

namespace App\Controller\Api;

use App\Entity\Maintenance;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Enum\MaintenanceStatusEnum;
use App\Enum\VehicleStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], 401);
        }

        $now = new \DateTimeImmutable('today');
        $next30Days = $now->modify('+30 days');
        $last30Days = $now->modify('-30 days');

        $upcoming = [
            ...$this->getUpcomingMaintenances($user, $now, $next30Days),
            ...$this->getUpcomingInsurances($user, $now, $next30Days),
            ...$this->getUpcomingInspections($user, $now, $next30Days),
        ];

        usort($upcoming, static fn (array $first, array $second): int => [$first['date'], $first['priority']] <=> [$second['date'], $second['priority']]);

        $recentActivity = [
            ...$this->getRecentMaintenances($user, $last30Days, $now),
            ...$this->getRecentInsurances($user, $last30Days, $now),
            ...$this->getRecentInspections($user, $last30Days, $now),
        ];

        usort($recentActivity, static fn (array $first, array $second): int => $second['date'] <=> $first['date']);

        return $this->json([
            'stats' => [
                'vehicles' => $this->countVehicles($user),
                'maintenances' => $this->countMaintenances($user),
                'maintenanceHealth' => $this->getMaintenanceHealth($user, $now),
                'alerts' => count($upcoming),
            ],
            'maintenanceHistory' => $this->getMaintenanceHistory($user, $now),
            'upcoming' => array_slice($upcoming, 0, 8),
            'recentActivity' => array_slice($recentActivity, 0, 8),
        ]);
    }

    private function countVehicles(User $user): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(Vehicle::class, 'v')
            ->andWhere('v.isDeleted = false')
            ->andWhere('v.status = :status')
            ->setParameter('status', VehicleStatusEnum::Active);

        $this->restrictVehicleToCurrentUser($qb, 'v', $user);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function countMaintenances(User $user): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(Maintenance::class, 'm')
            ->join('m.vehicle', 'v')
            ->andWhere('m.isDeleted = false');

        $this->restrictVehicleToCurrentUser($qb, 'v', $user);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array{percentage:int,upToDateVehicles:int,totalVehicles:int}
     */
    private function getMaintenanceHealth(User $user, \DateTimeImmutable $now): array
    {
        $vehiclesQb = $this->entityManager->createQueryBuilder()
            ->select('v')
            ->from(Vehicle::class, 'v')
            ->andWhere('v.isDeleted = false')
            ->andWhere('v.status = :status')
            ->setParameter('status', VehicleStatusEnum::Active);

        $this->restrictVehicleToCurrentUser($vehiclesQb, 'v', $user);

        $vehicles = $vehiclesQb->getQuery()->getResult();
        $totalVehicles = count($vehicles);

        if ($totalVehicles === 0) {
            return [
                'percentage' => 100,
                'upToDateVehicles' => 0,
                'totalVehicles' => 0,
            ];
        }

        $overdueVehicleIdsQb = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT v.id')
            ->from(Maintenance::class, 'm')
            ->join('m.vehicle', 'v')
            ->andWhere('m.isDeleted = false')
            ->andWhere('v.isDeleted = false')
            ->andWhere('v.status = :status')
            ->andWhere('(
                m.status IN (:openStatuses) AND m.plannedAt IS NOT NULL AND m.plannedAt < :today
            ) OR (
                m.status = :completedStatus AND m.nextDueAt IS NOT NULL AND m.nextDueAt < :today
            )')
            ->setParameter('status', VehicleStatusEnum::Active)
            ->setParameter('openStatuses', [MaintenanceStatusEnum::ToDo->value, MaintenanceStatusEnum::InProgress->value])
            ->setParameter('completedStatus', MaintenanceStatusEnum::Completed->value)
            ->setParameter('today', $now);

        $this->restrictVehicleToCurrentUser($overdueVehicleIdsQb, 'v', $user);

        $overdueVehicles = count($overdueVehicleIdsQb->getQuery()->getSingleColumnResult());
        $upToDateVehicles = max(0, $totalVehicles - $overdueVehicles);

        return [
            'percentage' => (int) round(($upToDateVehicles / $totalVehicles) * 100),
            'upToDateVehicles' => $upToDateVehicles,
            'totalVehicles' => $totalVehicles,
        ];
    }

    /**
     * @return array<int, array{month:string,count:int}>
     */
    private function getMaintenanceHistory(User $user, \DateTimeImmutable $now): array
    {
        $start = $now->modify('first day of this month')->modify('-11 months');
        $end = $now->modify('first day of next month');

        $qb = $this->entityManager->createQueryBuilder()
            ->select('m.performedAt')
            ->from(Maintenance::class, 'm')
            ->join('m.vehicle', 'v')
            ->andWhere('m.isDeleted = false')
            ->andWhere('m.performedAt IS NOT NULL')
            ->andWhere('m.performedAt >= :start')
            ->andWhere('m.performedAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        $this->restrictVehicleToCurrentUser($qb, 'v', $user);

        $counts = [];

        foreach ($qb->getQuery()->getResult() as $row) {
            $date = $row['performedAt'] ?? null;

            if ($date instanceof \DateTimeInterface) {
                $key = $date->format('Y-m');
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        $history = [];
        $formatter = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'MMM');

        for ($index = 0; $index < 12; $index++) {
            $month = $start->modify(sprintf('+%d months', $index));
            $key = $month->format('Y-m');
            $label = ucfirst((string) $formatter->format($month));

            $history[] = [
                'month' => rtrim($label, '.'),
                'count' => $counts[$key] ?? 0,
            ];
        }

        return $history;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getUpcomingMaintenances(User $user, \DateTimeImmutable $now, \DateTimeImmutable $next30Days): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('m', 'v', 'mt')
            ->from(Maintenance::class, 'm')
            ->join('m.vehicle', 'v')
            ->join('m.maintenanceType', 'mt')
            ->andWhere('m.isDeleted = false')
            ->andWhere('m.plannedAt IS NOT NULL')
            ->andWhere('m.plannedAt <= :next30Days')
            ->andWhere('m.status IN (:statuses)')
            ->setParameter('next30Days', $next30Days)
            ->setParameter('statuses', [MaintenanceStatusEnum::ToDo->value, MaintenanceStatusEnum::InProgress->value]);

        $this->restrictVehicleToCurrentUser($qb, 'v', $user);

        return array_map(
            fn (Maintenance $maintenance): array => $this->buildDashboardItem(
                type: 'maintenance',
                title: sprintf('Entretien %s', $maintenance->getMaintenanceType()?->getName() ?? 'prévu'),
                subtitle: $maintenance->getVehicle()?->displayName() ?? 'Véhicule inconnu',
                date: $maintenance->getPlannedAt(),
                now: $now,
                overdueLabel: 'En retard',
                upcomingLabel: 'Prévu',
            ),
            $qb->getQuery()->getResult(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getUpcomingInsurances(User $user, \DateTimeImmutable $now, \DateTimeImmutable $next30Days): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('i', 'v')
            ->from(VehicleInsurance::class, 'i')
            ->join('i.vehicle', 'v')
            ->andWhere('i.isDeleted = false')
            ->andWhere('i.endDate IS NOT NULL')
            ->andWhere('i.endDate > :today')
            ->andWhere('i.endDate <= :next30Days')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('next30Days', $next30Days);

        $this->restrictVehicleToCurrentUser($qb, 'v', $user);

        return array_map(
            fn (VehicleInsurance $insurance): array => $this->buildDashboardItem(
                type: 'insurance',
                title: sprintf('Assurance %s', $insurance->getProviderName()),
                subtitle: $insurance->getVehicle()?->displayName() ?? 'Véhicule inconnu',
                date: $insurance->getEndDate(),
                now: $now,
                overdueLabel: 'Expirée',
                upcomingLabel: 'Expire',
            ),
            $qb->getQuery()->getResult(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getUpcomingInspections(User $user, \DateTimeImmutable $now, \DateTimeImmutable $next30Days): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('vi', 'v')
            ->from(VehicleInspection::class, 'vi')
            ->join('vi.vehicle', 'v')
            ->andWhere('vi.isDeleted = false')
            ->andWhere('vi.validUntil <= :next30Days')
            ->setParameter('next30Days', $next30Days);

        $this->restrictVehicleToCurrentUser($qb, 'v', $user);

        return array_map(
            fn (VehicleInspection $inspection): array => $this->buildDashboardItem(
                type: 'inspection',
                title: 'Contrôle technique',
                subtitle: $inspection->getVehicle()?->displayName() ?? 'Véhicule inconnu',
                date: $inspection->getValidUntil(),
                now: $now,
                overdueLabel: 'Expiré',
                upcomingLabel: 'Expire',
            ),
            $qb->getQuery()->getResult(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRecentMaintenances(User $user, \DateTimeImmutable $last30Days, \DateTimeImmutable $now): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('m', 'v', 'mt')
            ->from(Maintenance::class, 'm')
            ->join('m.vehicle', 'v')
            ->join('m.maintenanceType', 'mt')
            ->andWhere('m.isDeleted = false')
            ->andWhere('m.performedAt BETWEEN :last30Days AND :now')
            ->setParameter('last30Days', $last30Days)
            ->setParameter('now', $now->modify('+1 day'));

        $this->restrictVehicleToCurrentUser($qb, 'v', $user);

        return array_map(
            fn (Maintenance $maintenance): array => $this->buildRecentItem(
                type: 'maintenance',
                title: sprintf('Entretien %s réalisé', $maintenance->getMaintenanceType()?->getName() ?? ''),
                subtitle: $maintenance->getVehicle()?->displayName() ?? 'Véhicule inconnu',
                date: $maintenance->getPerformedAt(),
            ),
            $qb->getQuery()->getResult(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRecentInsurances(User $user, \DateTimeImmutable $last30Days, \DateTimeImmutable $now): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('i', 'v')
            ->from(VehicleInsurance::class, 'i')
            ->join('i.vehicle', 'v')
            ->andWhere('i.isDeleted = false')
            ->andWhere('i.createdAt BETWEEN :last30Days AND :now')
            ->setParameter('last30Days', $last30Days)
            ->setParameter('now', $now->modify('+1 day'));

        $this->restrictVehicleToCurrentUser($qb, 'v', $user);

        return array_map(
            fn (VehicleInsurance $insurance): array => $this->buildRecentItem(
                type: 'insurance',
                title: sprintf('Assurance %s ajoutée', $insurance->getProviderName()),
                subtitle: $insurance->getVehicle()?->displayName() ?? 'Véhicule inconnu',
                date: $insurance->getCreatedAt(),
            ),
            $qb->getQuery()->getResult(),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getRecentInspections(User $user, \DateTimeImmutable $last30Days, \DateTimeImmutable $now): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('vi', 'v')
            ->from(VehicleInspection::class, 'vi')
            ->join('vi.vehicle', 'v')
            ->andWhere('vi.isDeleted = false')
            ->andWhere('vi.inspectionDate BETWEEN :last30Days AND :now')
            ->setParameter('last30Days', $last30Days)
            ->setParameter('now', $now->modify('+1 day'));

        $this->restrictVehicleToCurrentUser($qb, 'v', $user);

        return array_map(
            fn (VehicleInspection $inspection): array => $this->buildRecentItem(
                type: 'inspection',
                title: 'Contrôle technique ajouté',
                subtitle: $inspection->getVehicle()?->displayName() ?? 'Véhicule inconnu',
                date: $inspection->getInspectionDate(),
            ),
            $qb->getQuery()->getResult(),
        );
    }

    /**
     * @return array{type:string,severity:string,priority:int,title:string,subtitle:string,date:string,meta:string}
     */
    private function buildDashboardItem(
        string $type,
        string $title,
        string $subtitle,
        ?\DateTimeImmutable $date,
        \DateTimeImmutable $now,
        string $overdueLabel,
        string $upcomingLabel,
    ): array {
        $days = $date !== null ? (int) $now->diff($date)->format('%r%a') : 0;
        $isOverdue = $days < 0;

        return [
            'type' => $type,
            'severity' => $isOverdue ? 'danger' : 'warning',
            'priority' => $isOverdue ? 0 : 1,
            'title' => $title,
            'subtitle' => $subtitle,
            'date' => $this->formatDate($date),
            'meta' => $this->formatRelativeDueDate($days, $isOverdue ? $overdueLabel : $upcomingLabel),
        ];
    }

    /**
     * @return array{type:string,title:string,subtitle:string,date:string,meta:string}
     */
    private function buildRecentItem(string $type, string $title, string $subtitle, ?\DateTimeImmutable $date): array
    {
        return [
            'type' => $type,
            'title' => trim($title),
            'subtitle' => $subtitle,
            'date' => $this->formatDate($date),
            'meta' => $date?->format('d/m/Y') ?? '',
        ];
    }

    private function restrictVehicleToCurrentUser(QueryBuilder $queryBuilder, string $vehicleAlias, User $user): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $queryBuilder
            ->andWhere(sprintf('%s.user = :currentUser', $vehicleAlias))
            ->setParameter('currentUser', $user);
    }

    private function formatDate(?\DateTimeImmutable $date): string
    {
        return $date?->format('Y-m-d') ?? '';
    }

    private function formatRelativeDueDate(int $days, string $label): string
    {
        if ($days === 0) {
            return sprintf('%s aujourd\'hui', $label);
        }

        if ($days < 0) {
            return sprintf('%s depuis %d j', $label, abs($days));
        }

        return sprintf('%s dans %d j', $label, $days);
    }
}
