<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ReminderNotification;
use PHPUnit\Framework\TestCase;

final class ReminderNotificationTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $notification = new ReminderNotification();

        self::assertNull($notification->getId());
        self::assertSame('', $notification->getTargetType());
        self::assertSame(0, $notification->getTargetId());
        self::assertSame('', $notification->getTimeToDeadline());
        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $notification->getDeadline(),
        );
        self::assertInstanceOf(
            \DateTimeImmutable::class,
            $notification->getSentAt(),
        );
    }

    public function testAccessors(): void
    {
        $deadline = new \DateTimeImmutable('2026-06-09');
        $sentAt = new \DateTimeImmutable('2026-06-02 08:15:00');
        $notification = new ReminderNotification();

        self::assertSame($notification, $notification->setTargetType('maintenance'));
        self::assertSame($notification, $notification->setTargetId(42));
        self::assertSame($notification, $notification->setDeadline($deadline));
        self::assertSame($notification, $notification->setTimeToDeadline('1 semaine'));
        self::assertSame($notification, $notification->setSentAt($sentAt));

        self::assertSame('maintenance', $notification->getTargetType());
        self::assertSame(42, $notification->getTargetId());
        self::assertSame($deadline, $notification->getDeadline());
        self::assertSame('1 semaine', $notification->getTimeToDeadline());
        self::assertSame($sentAt, $notification->getSentAt());
    }
}
