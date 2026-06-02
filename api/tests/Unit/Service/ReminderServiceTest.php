<?php

namespace App\Tests\Unit\Service;

use App\Entity\Maintenance;
use App\Entity\MaintenanceType;
use App\Entity\ReminderNotification;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Enum\InspectionResultEnum;
use App\Enum\MaintenanceStatusEnum;
use App\Repository\MaintenanceRepository;
use App\Repository\ReminderNotificationRepository;
use App\Repository\VehicleInspectionRepository;
use App\Service\ReminderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class ReminderServiceTest extends TestCase
{
    public function testGetRemindersReturnsTodoMaintenanceAndInspectionWindows(): void
    {
        $vehicle = $this->vehicle(10);
        $maintenance = $this->maintenance(20, $vehicle, '2026-06-09');
        $inspection = $this->inspection(30, $vehicle, '2026-06-04');
        $service = $this->service(
            maintenanceByDate: ['2026-06-09' => [$maintenance]],
            inspectionByDate: ['2026-06-04' => [$inspection]],
        );

        $reminders = $service->getReminders(new \DateTimeImmutable('2026-06-02'));

        self::assertCount(2, $reminders);
        self::assertSame("l'intervention", $reminders[0]['type']);
        self::assertSame('09-06-2026', $reminders[0]['deadline']);
        self::assertSame('1 semaine', $reminders[0]['timeToDeadline']);
        self::assertSame("de l'intervention", $reminders[0]['cta']['label']);
        self::assertSame(
            'https://app.test/vehicles/10/interventions/20',
            $reminders[0]['cta']['link'],
        );
        self::assertSame('le contrôle technique', $reminders[1]['type']);
        self::assertSame('04-06-2026', $reminders[1]['deadline']);
        self::assertSame('48 heures', $reminders[1]['timeToDeadline']);
        self::assertArrayNotHasKey('targetType', $reminders[0]);
    }

    public function testGetRemindersSkipsAlreadySentReminder(): void
    {
        $vehicle = $this->vehicle(10);
        $maintenance = $this->maintenance(20, $vehicle, '2026-06-09');
        $service = $this->service(
            maintenanceByDate: ['2026-06-09' => [$maintenance]],
            sent: ['maintenance:20:2026-06-09:1 semaine' => true],
        );

        self::assertSame([], $service->getReminders(new \DateTimeImmutable('2026-06-02')));
    }

    public function testSendReminderSendsMailAndTracksNotification(): void
    {
        $vehicle = $this->vehicle(10);
        $maintenance = $this->maintenance(20, $vehicle, '2026-06-09');
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('reminder/email.html.twig', $email->getHtmlTemplate());
                self::assertSame('Rappel GMAO - échéance dans 1 semaine', $email->getSubject());

                return true;
            }));
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(function (ReminderNotification $notification): bool {
                self::assertSame('maintenance', $notification->getTargetType());
                self::assertSame(20, $notification->getTargetId());
                self::assertSame(
                    '2026-06-09',
                    $notification->getDeadline()->format('Y-m-d'),
                );
                self::assertSame('1 semaine', $notification->getTimeToDeadline());

                return true;
            }));
        $entityManager->expects(self::once())->method('flush');
        $service = $this->service(
            maintenanceByDate: ['2026-06-09' => [$maintenance]],
            mailer: $mailer,
            entityManager: $entityManager,
        );

        self::assertSame(1, $service->sendReminder(new \DateTimeImmutable('2026-06-02')));
    }

    public function testSendDebugReminderSendsExampleMail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email): bool {
                self::assertSame('debug@example.com', $email->getTo()[0]->getAddress());
                self::assertSame('reminder/email.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        $this->service(mailer: $mailer)->sendDebugReminder('debug@example.com');
    }

    public function testSendReminderSkipsUsersWithoutEmail(): void
    {
        $vehicle = $this->vehicle(10);
        $vehicle->getUser()->setEmail('');
        $maintenance = $this->maintenance(20, $vehicle, '2026-06-09');
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');
        $service = $this->service(
            maintenanceByDate: ['2026-06-09' => [$maintenance]],
            mailer: $mailer,
            entityManager: $entityManager,
        );

        self::assertSame(0, $service->sendReminder(new \DateTimeImmutable('2026-06-02')));
    }

    public function testGetRemindersSkipsRecordsWithoutVehicleAndSentInspections(): void
    {
        $maintenance = new Maintenance();
        $sentInspection = new VehicleInspection();
        $inspectionWithoutVehicle = new VehicleInspection();

        $this->setId($maintenance, 20);
        $this->setId($sentInspection, 30);
        $this->setId($inspectionWithoutVehicle, 31);
        $service = $this->service(
            maintenanceByDate: ['2026-06-09' => [$maintenance]],
            inspectionByDate: ['2026-06-04' => [
                $sentInspection,
                $inspectionWithoutVehicle,
            ]],
            sent: ['inspection:30:2026-06-04:48 heures' => true],
        );

        self::assertSame([], $service->getReminders(new \DateTimeImmutable('2026-06-02')));
    }

    /**
     * @param array<string, Maintenance[]> $maintenanceByDate
     * @param array<string, VehicleInspection[]> $inspectionByDate
     * @param array<string, bool> $sent
     */
    private function service(
        array $maintenanceByDate = [],
        array $inspectionByDate = [],
        array $sent = [],
        ?MailerInterface $mailer = null,
        ?EntityManagerInterface $entityManager = null,
    ): ReminderService {
        $maintenances = $this->createMock(MaintenanceRepository::class);
        $maintenances->method('findTodoScheduledForReminderDate')
            ->willReturnCallback(fn (\DateTimeImmutable $date): array => (
                $maintenanceByDate[$date->format('Y-m-d')] ?? []
            ));
        $inspections = $this->createMock(VehicleInspectionRepository::class);
        $inspections->method('findExpiringForReminderDate')
            ->willReturnCallback(fn (\DateTimeImmutable $date): array => (
                $inspectionByDate[$date->format('Y-m-d')] ?? []
            ));
        $notifications = $this->createMock(ReminderNotificationRepository::class);
        $notifications->method('wasSent')
            ->willReturnCallback(fn (
                string $type,
                int $id,
                \DateTimeImmutable $deadline,
                string $label,
            ): bool => (
                $sent[sprintf(
                    '%s:%d:%s:%s',
                    $type,
                    $id,
                    $deadline->format('Y-m-d'),
                    $label,
                )] ?? false
            ));

        return new ReminderService(
            $maintenances,
            $inspections,
            $notifications,
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $mailer ?? $this->createMock(MailerInterface::class),
            'https://app.test',
        );
    }

    private function vehicle(int $id): Vehicle
    {
        $user = (new User())
            ->setEmail('owner@example.com')
            ->setFirstname('john')
            ->setLastname('doe');
        $vehicle = (new Vehicle())
            ->setName('clio')
            ->setRegistration('AA-123-AA')
            ->setBrand('renault')
            ->setModel('clio')
            ->setUser($user);

        $this->setId($vehicle, $id);

        return $vehicle;
    }

    private function maintenance(int $id, Vehicle $vehicle, string $plannedAt): Maintenance
    {
        $maintenance = (new Maintenance())
            ->setVehicle($vehicle)
            ->setMaintenanceType((new MaintenanceType())->setName('Vidange'))
            ->setPlannedAt(new \DateTimeImmutable($plannedAt))
            ->setStatus(MaintenanceStatusEnum::ToDo);

        $this->setId($maintenance, $id);

        return $maintenance;
    }

    private function inspection(int $id, Vehicle $vehicle, string $validUntil): VehicleInspection
    {
        $inspection = (new VehicleInspection())
            ->setVehicle($vehicle)
            ->setInspectionDate(new \DateTimeImmutable('2024-06-04'))
            ->setValidUntil(new \DateTimeImmutable($validUntil))
            ->setMileage(120000)
            ->setResult(InspectionResultEnum::Pass);

        $this->setId($inspection, $id);

        return $inspection;
    }

    private function setId(object $object, int $id): void
    {
        $property = new \ReflectionProperty($object, 'id');
        $property->setValue($object, $id);
    }
}
