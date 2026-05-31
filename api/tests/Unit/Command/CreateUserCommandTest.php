<?php

namespace App\Tests\Unit\Command;

use App\Command\CreateUserCommand;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateUserCommandTest extends TestCase
{
    public function testExecuteCreatesUserAndFlushes(): void
    {
        $createdUser = null;
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())
            ->method('hashPassword')
            ->with(self::isInstanceOf(User::class), 'secret')
            ->willReturn('hashed-password');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('persist')
            ->with(self::callback(static function (User $user) use (&$createdUser): bool {
                $createdUser = $user;

                return true;
            }));
        $em->expects(self::once())->method('flush');
        $tester = new CommandTester(new CreateUserCommand($em, $hasher));

        $exitCode = $tester->execute([
            'email' => 'test@example.com',
            'password' => 'secret',
            'firstname' => 'Jane',
            'lastname' => 'Doe',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertInstanceOf(User::class, $createdUser);
        self::assertSame('test@example.com', $createdUser->getEmail());
        self::assertSame('jane', $createdUser->getFirstname());
        self::assertSame('doe', $createdUser->getLastname());
        self::assertSame('hashed-password', $createdUser->getPassword());
        self::assertStringContainsString('Utilisateur créé avec succès', $tester->getDisplay());
    }
}
