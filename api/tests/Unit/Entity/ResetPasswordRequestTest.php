<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class ResetPasswordRequestTest extends TestCase
{
    public function testConstructorInitializesRequest(): void
    {
        $user = new User();
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $request = new ResetPasswordRequest($user, $expiresAt, 'selector', 'hashed-token');

        self::assertNull($request->getId());
        self::assertSame($user, $request->getUser());
        self::assertSame($expiresAt, $request->getExpiresAt());
    }
}
