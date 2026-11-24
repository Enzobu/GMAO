<?php

namespace App\Tests\Integration;

use ApiPlatform\Metadata\Post;
use App\ApiPlatform\State\MaintenanceStateProcessor;
use App\Entity\Maintenance;
use App\Entity\MaintenancePart;
use App\Entity\Part;
use App\Entity\PartType;
use App\Enum\MaintenanceStatusEnum;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class StockTransactionTest extends KernelTestCase
{
    public function testInsufficientSecondPartRollsBackFirstDebit(): void
    {
        self::bootKernel();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();
        $connection = $em->getConnection();

        $type = (new PartType())->setName('transaction-test-'.bin2hex(random_bytes(8)));
        $first = (new Part())->setPartType($type)->setQuantity(3);
        $second = (new Part())->setPartType($type)->setQuantity(0);

        $em->persist($type);
        $em->persist($first);
        $em->persist($second);
        $em->flush();

        $firstId = $first->getId();
        $secondId = $second->getId();
        $typeId = $type->getId();

        try {
            $maintenance = (new Maintenance())
                ->setStatus(MaintenanceStatusEnum::Completed)
                ->setFinishedAt(new \DateTimeImmutable())
                ->setMileage(1000)
                ->addMaintenancePart(
                    (new MaintenancePart())->setPart($first)->setQuantity(1),
                )
                ->addMaintenancePart(
                    (new MaintenancePart())->setPart($second)->setQuantity(1),
                );

            $security = $this->createMock(Security::class);
            $security->method('isGranted')->willReturn(true);
            $vehicleManager = $this->createMock(VehicleManager::class);

            $processor = new MaintenanceStateProcessor(
                $em,
                $security,
                $vehicleManager,
                new RequestStack(),
            );

            try {
                $processor->process($maintenance, new Post());
                self::fail('Une consommation supérieure au stock doit être refusée.');
            } catch (ConflictHttpException $exception) {
                self::assertStringContainsString('Stock insuffisant', $exception->getMessage());
            }

            self::assertSame(3, (int) $connection->fetchOne(
                'SELECT quantity FROM part WHERE id = ?',
                [$firstId],
            ));
            self::assertSame(0, (int) $connection->fetchOne(
                'SELECT quantity FROM part WHERE id = ?',
                [$secondId],
            ));
        } finally {
            $connection->executeStatement(
                'DELETE FROM part WHERE id IN (?, ?)',
                [$firstId, $secondId],
            );
            $connection->executeStatement(
                'DELETE FROM part_type WHERE id = ?',
                [$typeId],
            );
        }
    }
}
