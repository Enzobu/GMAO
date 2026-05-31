<?php

namespace App\Tests\Unit\Repository;

use App\Entity\PartType;
use App\Repository\PartTypeRepository;

final class PartTypeRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(PartTypeRepository::class, $this->instantiateRepository(PartTypeRepository::class, PartType::class));
    }
}
