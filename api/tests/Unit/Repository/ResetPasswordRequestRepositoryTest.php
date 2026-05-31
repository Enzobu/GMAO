<?php

namespace App\Tests\Unit\Repository;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Repository\ResetPasswordRequestRepository;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;

final class ResetPasswordRequestRepositoryTest extends RepositoryTestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(ResetPasswordRequestRepository::class, $this->instantiateRepository(ResetPasswordRequestRepository::class, ResetPasswordRequest::class));
    }

    public function testCreatesResetPasswordRequest(): void
    {
        $repository = $this->instantiateRepository(ResetPasswordRequestRepository::class, ResetPasswordRequest::class);
        $user = new User();
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $request = $repository->createResetPasswordRequest($user, $expiresAt, 'selector', 'hashed-token');

        self::assertInstanceOf(ResetPasswordRequestInterface::class, $request);
        self::assertSame($user, $request->getUser());
        self::assertSame($expiresAt, $request->getExpiresAt());
        self::assertSame('hashed-token', $request->getHashedToken());
    }
}
