<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Document;
use App\Entity\Address;
use App\Entity\User;
use App\Entity\Vehicle;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testNamesAreLowercasedAndDisplayNameIsFormatted(): void
    {
        $emptyUser = new User();
        self::assertNull($emptyUser->getId());

        $user = (new User())
            ->setEmail('jean@example.com')
            ->setPassword('hashed')
            ->setAddress(new Address())
            ->setFirstname('Jean')
            ->setLastname('Dupont');

        self::assertSame('jean@example.com', $user->getEmail());
        self::assertSame('jean@example.com', $user->getUserIdentifier());
        self::assertSame('hashed', $user->getPassword());
        self::assertInstanceOf(Address::class, $user->getAddress());
        self::assertSame('jean', $user->getFirstname());
        self::assertSame('dupont', $user->getLastname());
        self::assertSame('Jean DUPONT', $user->displayName());

        $user->eraseCredentials();
        self::assertSame('hashed', $user->getPassword());
    }

    public function testRolesAlwaysContainRoleUserOnce(): void
    {
        $user = (new User())->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], array_values($user->getRoles()));
    }

    public function testVehicleRelationIsBidirectional(): void
    {
        $user = new User();
        $vehicle = new Vehicle();

        $user->addVehicle($vehicle);
        $user->addVehicle($vehicle);

        self::assertTrue($user->getVehicles()->contains($vehicle));
        self::assertCount(1, $user->getVehicles());
        self::assertSame($user, $vehicle->getUser());

        $user->removeVehicle($vehicle);

        self::assertFalse($user->getVehicles()->contains($vehicle));
        self::assertNull($vehicle->getUser());

        $otherUser = new User();
        $user->addVehicle($vehicle);
        $vehicle->setUser($otherUser);
        $user->removeVehicle($vehicle);
        self::assertSame($otherUser, $vehicle->getUser());
    }

    public function testRemovingDocumentSoftDeletesIt(): void
    {
        $user = new User();
        $document = new Document();

        $user->addDocument($document);
        $user->addDocument($document);
        self::assertCount(1, $user->getDocuments());
        $user->removeDocument($document);

        self::assertTrue($document->isDeleted());

        $user->removeDocument($document);
        self::assertTrue($document->isDeleted());
    }

    public function testSoftDeleteFlagCanBeChanged(): void
    {
        $user = new User();

        self::assertFalse($user->isDeleted());
        $user->setIsDeleted(true);
        self::assertTrue($user->isDeleted());
    }
}
