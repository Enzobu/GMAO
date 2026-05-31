<?php

namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class UserRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(UserRepository::class, $this->instantiateRepository(UserRepository::class, User::class));
    }

    public function testUpgradesPasswordForUser(): void
    {
        [$repository, $entityManager] = $this->instantiateRepositoryWithEntityManager(UserRepository::class, User::class);
        $user = new User();

        $entityManager->expects(self::once())->method('persist')->with($user);
        $entityManager->expects(self::once())->method('flush');

        $repository->upgradePassword($user, 'new-hash');

        self::assertSame('new-hash', $user->getPassword());
    }

    public function testUpgradePasswordRejectsUnsupportedUser(): void
    {
        $repository = $this->instantiateRepository(UserRepository::class, User::class);
        $unsupportedUser = new class implements PasswordAuthenticatedUserInterface {
            public function getPassword(): ?string
            {
                return 'hash';
            }
        };

        $this->expectException(UnsupportedUserException::class);

        $repository->upgradePassword($unsupportedUser, 'new-hash');
    }
}
