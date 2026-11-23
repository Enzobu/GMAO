<?php

namespace App\Tests\Unit\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\State\PartStateProcessor;
use App\Entity\MaintenancePart;
use App\Entity\Part;
use App\Entity\PartType;
use App\Service\PartCompatibilityGuard;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PartStateProcessorTest extends TestCase
{
    public function testReturnsNullForUnsupportedData(): void
    {
        self::assertNull($this->processor()->process(new \stdClass(), new Post()));
    }

    public function testPersistsNewPartAndChecksCompatibility(): void
    {
        $type = new PartType();
        $part = (new Part())->setPartType($type)->setQuantity(2);
        $em = $this->entityManager();
        $em->expects(self::once())->method('persist')->with($part);
        $em->expects(self::once())->method('flush');

        $guard = $this->createMock(PartCompatibilityGuard::class);
        $guard->expects(self::once())->method('assertNoOverlap')->with($part);

        self::assertSame($part, $this->processor($em, $guard)->process($part, new Post()));
    }

    public function testDeletesUnusedPartPermanently(): void
    {
        $part = new Part();
        $em = $this->entityManager();
        $em->expects(self::once())->method('remove')->with($part);
        $em->expects(self::once())->method('flush');

        self::assertNull($this->processor($em)->process($part, new Delete()));
    }

    public function testKeepsHistoricalPartSoftDeleted(): void
    {
        $part = new Part();
        $part->addMaintenancePart(new MaintenancePart());
        $em = $this->entityManager();
        $em->expects(self::never())->method('remove');
        $em->expects(self::once())->method('flush');

        self::assertNull($this->processor($em)->process($part, new Delete()));
        self::assertTrue($part->isDeleted());
    }

    private function processor(
        ?EntityManagerInterface $em = null,
        ?PartCompatibilityGuard $guard = null,
    ): PartStateProcessor {
        return new PartStateProcessor(
            $em ?? $this->entityManager(),
            $guard ?? $this->createMock(PartCompatibilityGuard::class),
        );
    }

    private function entityManager(): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $callback) => $callback());

        return $em;
    }
}
