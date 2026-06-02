<?php

namespace App\Service;

use App\Entity\Maintenance;
use App\Entity\ReminderNotification;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Repository\MaintenanceRepository;
use App\Repository\ReminderNotificationRepository;
use App\Repository\VehicleInspectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;

final readonly class ReminderService
{
    private const TYPE_MAINTENANCE = 'maintenance';
    private const TYPE_INSPECTION = 'inspection';
    private const LABEL_ONE_WEEK = '1 semaine';
    private const LABEL_TWO_DAYS = '48 heures';
    private const OFFSET_ONE_WEEK = '+7 days';
    private const OFFSET_TWO_DAYS = '+2 days';

    public function __construct(
        private MaintenanceRepository $maintenances,
        private VehicleInspectionRepository $inspections,
        private ReminderNotificationRepository $notifications,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private string $frontendUrl,
    ) {}

    /** @return list<array<string, mixed>> */
    public function getReminders(?\DateTimeImmutable $today = null): array
    {
        return array_map(
            fn (array $reminder): array => $this->publicReminder($reminder),
            $this->collectReminders($today),
        );
    }

    public function sendReminder(?\DateTimeImmutable $today = null): int
    {
        $sentCount = 0;

        foreach ($this->collectReminders($today) as $reminder) {
            $user = $reminder['user'];

            if (!$user instanceof User || !$user->getEmail()) {
                continue;
            }

            $this->mailer->send($this->email((string) $user->getEmail(), $reminder));
            $this->trackReminder($reminder);
            ++$sentCount;
        }

        if ($sentCount > 0) {
            $this->entityManager->flush();
        }

        return $sentCount;
    }

    public function sendDebugReminder(string $email): void
    {
        $this->mailer->send($this->email($email, $this->debugReminder()));
    }

    /** @return list<array<string, mixed>> */
    private function collectReminders(?\DateTimeImmutable $today): array
    {
        $today ??= new \DateTimeImmutable('today');
        $reminders = [];

        foreach ($this->windows($today) as $window) {
            $reminders = [
                ...$reminders,
                ...$this->maintenanceReminders($window['date'], $window['label']),
                ...$this->inspectionReminders($window['date'], $window['label']),
            ];
        }

        return $reminders;
    }

    /** @return list<array{date:\DateTimeImmutable,label:string}> */
    private function windows(\DateTimeImmutable $today): array
    {
        return [
            ['date' => $today->modify(self::OFFSET_ONE_WEEK), 'label' => self::LABEL_ONE_WEEK],
            ['date' => $today->modify(self::OFFSET_TWO_DAYS), 'label' => self::LABEL_TWO_DAYS],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function maintenanceReminders(
        \DateTimeImmutable $date,
        string $timeToDeadline,
    ): array {
        $reminders = [];

        foreach ($this->maintenances->findTodoScheduledForReminderDate($date) as $maintenance) {
            if ($this->wasSent(
                self::TYPE_MAINTENANCE,
                $maintenance->getId(),
                $date,
                $timeToDeadline,
            )) {
                continue;
            }

            $vehicle = $maintenance->getVehicle();

            if (!$vehicle) {
                continue;
            }

            $reminders[] = $this->reminder(
                target: [self::TYPE_MAINTENANCE, (int) $maintenance->getId()],
                vehicle: $vehicle,
                type: "l'intervention",
                deadline: $date,
                timeToDeadline: $timeToDeadline,
                cta: [
                    "de l'intervention",
                    $this->frontendPath(sprintf(
                        '/vehicles/%d/interventions/%d',
                        $vehicle->getId(),
                        $maintenance->getId(),
                    )),
                ],
            );
        }

        return $reminders;
    }

    /** @return list<array<string, mixed>> */
    private function inspectionReminders(
        \DateTimeImmutable $date,
        string $timeToDeadline,
    ): array {
        $reminders = [];

        foreach ($this->inspections->findExpiringForReminderDate($date) as $inspection) {
            if ($this->wasSent(
                self::TYPE_INSPECTION,
                $inspection->getId(),
                $date,
                $timeToDeadline,
            )) {
                continue;
            }

            $vehicle = $inspection->getVehicle();

            if (!$vehicle) {
                continue;
            }

            $reminders[] = $this->reminder(
                target: [self::TYPE_INSPECTION, (int) $inspection->getId()],
                vehicle: $vehicle,
                type: 'le contrôle technique',
                deadline: $date,
                timeToDeadline: $timeToDeadline,
                cta: [
                    'du contrôle technique',
                    $this->frontendPath(sprintf(
                        '/vehicles/%d/inspections/%d',
                        $vehicle->getId(),
                        $inspection->getId(),
                    )),
                ],
            );
        }

        return $reminders;
    }

    private function wasSent(
        string $type,
        ?int $id,
        \DateTimeImmutable $deadline,
        string $timeToDeadline,
    ): bool {
        return $id === null || $this->notifications->wasSent(
            $type,
            $id,
            $deadline,
            $timeToDeadline,
        );
    }

    /**
     * @param array{0: string, 1: int} $target
     * @param array{0: string, 1: string} $cta
     *
     * @return array<string, mixed>
     */
    private function reminder(
        array $target,
        Vehicle $vehicle,
        string $type,
        \DateTimeImmutable $deadline,
        string $timeToDeadline,
        array $cta,
    ): array {
        return [
            'targetType' => $target[0],
            'targetId' => $target[1],
            'vehicle' => $vehicle,
            'user' => $vehicle->getUser(),
            'type' => $type,
            'deadline' => $deadline->format('d-m-Y'),
            'deadlineDate' => $deadline,
            'timeToDeadline' => $timeToDeadline,
            'cta' => ['label' => $cta[0], 'link' => $cta[1]],
        ];
    }

    /** @return array<string, mixed> */
    private function publicReminder(array $reminder): array
    {
        unset($reminder['targetType'], $reminder['targetId'], $reminder['deadlineDate']);

        return $reminder;
    }

    private function trackReminder(array $reminder): void
    {
        $this->entityManager->persist((new ReminderNotification())
            ->setTargetType($reminder['targetType'])
            ->setTargetId($reminder['targetId'])
            ->setDeadline($reminder['deadlineDate'])
            ->setTimeToDeadline($reminder['timeToDeadline'])
            ->setSentAt(new \DateTimeImmutable()));
    }

    private function email(string $recipient, array $reminder): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from(new EmailAddress('no-reply@enzo-palermo.com', 'Enzo PALERMO'))
            ->to($recipient)
            ->subject(sprintf('Rappel GMAO - échéance dans %s', $reminder['timeToDeadline']))
            ->htmlTemplate('reminder/email.html.twig')
            ->context([
                'reminder' => $this->publicReminder($reminder),
                'user' => $reminder['user'],
            ]);
    }

    /** @return array<string, mixed> */
    private function debugReminder(): array
    {
        $user = ['firstname' => 'jean', 'lastname' => 'dupont'];
        $vehicle = ['name' => 'clio'];

        return [
            'targetType' => self::TYPE_MAINTENANCE,
            'targetId' => 0,
            'vehicle' => $vehicle,
            'user' => $user,
            'type' => "l'intervention",
            'deadline' => (new \DateTimeImmutable(self::OFFSET_ONE_WEEK))->format('d-m-Y'),
            'deadlineDate' => new \DateTimeImmutable(self::OFFSET_ONE_WEEK),
            'timeToDeadline' => self::LABEL_ONE_WEEK,
            'cta' => [
                'label' => "de l'intervention",
                'link' => $this->frontendPath('/vehicles/1/interventions/1'),
            ],
        ];
    }

    private function frontendPath(string $path): string
    {
        return rtrim($this->frontendUrl, '/').$path;
    }
}
