<?php

namespace App\Entity;

use App\Repository\ReminderNotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReminderNotificationRepository::class)]
#[ORM\UniqueConstraint(
    name: 'uniq_reminder_notification_target',
    fields: ['targetType', 'targetId', 'deadline', 'timeToDeadline']
)]
class ReminderNotification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $targetType = '';

    #[ORM\Column]
    private int $targetId = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $deadline;

    #[ORM\Column(length: 20)]
    private string $timeToDeadline = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $sentAt;

    public function __construct()
    {
        $this->deadline = new \DateTimeImmutable('today');
        $this->sentAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function setTargetType(string $targetType): static
    {
        $this->targetType = $targetType;

        return $this;
    }

    public function getTargetId(): int
    {
        return $this->targetId;
    }

    public function setTargetId(int $targetId): static
    {
        $this->targetId = $targetId;

        return $this;
    }

    public function getDeadline(): \DateTimeImmutable
    {
        return $this->deadline;
    }

    public function setDeadline(\DateTimeImmutable $deadline): static
    {
        $this->deadline = $deadline;

        return $this;
    }

    public function getTimeToDeadline(): string
    {
        return $this->timeToDeadline;
    }

    public function setTimeToDeadline(string $timeToDeadline): static
    {
        $this->timeToDeadline = $timeToDeadline;

        return $this;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;

        return $this;
    }
}
