<?php

namespace App\Tests\Unit\Repository;

use App\Entity\ReminderNotification;
use App\Repository\ReminderNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;

final class ReminderNotificationRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(
            ReminderNotificationRepository::class,
            $this->instantiateRepository(
                ReminderNotificationRepository::class,
                ReminderNotification::class,
            ),
        );
    }

    public function testWasSentReturnsTrueWhenMatchingRowExists(): void
    {
        $repository = $this->createRepositoryCounting(1, [
            'targetType' => 'maintenance',
            'targetId' => 42,
            'deadline' => new \DateTimeImmutable('2026-06-09'),
            'timeToDeadline' => '1 semaine',
        ]);

        self::assertTrue(
            $repository->wasSent(
                'maintenance',
                42,
                new \DateTimeImmutable('2026-06-09'),
                '1 semaine',
            ),
        );
    }

    public function testWasSentReturnsFalseWhenNoMatchingRowExists(): void
    {
        $repository = $this->createRepositoryCounting(0, [
            'targetType' => 'inspection',
            'targetId' => 7,
            'deadline' => new \DateTimeImmutable('2026-06-04'),
            'timeToDeadline' => '48 heures',
        ]);

        self::assertFalse(
            $repository->wasSent(
                'inspection',
                7,
                new \DateTimeImmutable('2026-06-04'),
                '48 heures',
            ),
        );
    }

    /**
     * @param array{
     *     targetType: string,
     *     targetId: int,
     *     deadline: \DateTimeImmutable,
     *     timeToDeadline: string
     * } $criteria
     */
    private function createRepositoryCounting(
        int $count,
        array $criteria,
    ): ReminderNotificationRepository
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->name = ReminderNotification::class;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('getClassMetadata')
            ->with(ReminderNotification::class)
            ->willReturn($metadata);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getManagerForClass')
            ->with(ReminderNotification::class)
            ->willReturn($entityManager);

        $repository = $this->getMockBuilder(ReminderNotificationRepository::class)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['count'])
            ->getMock();

        $repository
            ->expects($this->once())
            ->method('count')
            ->with($criteria)
            ->willReturn($count);

        return $repository;
    }
}
