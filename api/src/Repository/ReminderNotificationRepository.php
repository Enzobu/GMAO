<?php

namespace App\Repository;

use App\Entity\ReminderNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReminderNotification>
 */
class ReminderNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReminderNotification::class);
    }

    public function wasSent(
        string $targetType,
        int $targetId,
        \DateTimeImmutable $deadline,
        string $timeToDeadline,
    ): bool {
        return $this->count([
            'targetType' => $targetType,
            'targetId' => $targetId,
            'deadline' => $deadline,
            'timeToDeadline' => $timeToDeadline,
        ]) > 0;
    }
}
