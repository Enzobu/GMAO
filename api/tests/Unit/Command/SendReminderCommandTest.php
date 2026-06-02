<?php

namespace App\Tests\Unit\Command;

use App\Command\DebugSendReminderCommand;
use App\Command\SendReminderCommand;
use App\Repository\MaintenanceRepository;
use App\Repository\ReminderNotificationRepository;
use App\Repository\VehicleInspectionRepository;
use App\Service\ReminderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\MailerInterface;

final class SendReminderCommandTest extends TestCase
{
    public function testSendReminderCommandOutputsSentCount(): void
    {
        $tester = new CommandTester(new SendReminderCommand($this->service()));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('0 rappel(s) envoyé(s).', $tester->getDisplay());
    }

    public function testDebugSendReminderCommandSendsExample(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');
        $tester = new CommandTester(new DebugSendReminderCommand($this->service($mailer)));

        $exitCode = $tester->execute(['mail' => 'debug@example.com']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('debug@example.com', $tester->getDisplay());
    }

    private function service(?MailerInterface $mailer = null): ReminderService
    {
        $maintenances = $this->createMock(MaintenanceRepository::class);
        $maintenances->method('findTodoScheduledForReminderDate')->willReturn([]);
        $inspections = $this->createMock(VehicleInspectionRepository::class);
        $inspections->method('findExpiringForReminderDate')->willReturn([]);
        $notifications = $this->createMock(ReminderNotificationRepository::class);

        return new ReminderService(
            $maintenances,
            $inspections,
            $notifications,
            $this->createMock(EntityManagerInterface::class),
            $mailer ?? $this->createMock(MailerInterface::class),
            'https://app.test',
        );
    }
}
