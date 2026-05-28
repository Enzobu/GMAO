<?php

namespace App\Tests\Unit\Controller\Api;

use App\Controller\Api\ResetPasswordController;
use App\Entity\User;
use App\Tests\Unit\Controller\ControllerTestContainer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final class ResetPasswordControllerTest extends TestCase
{
    public function testRejectsInvalidJson(): void
    {
        $response = $this->controller()->reset(
            'token',
            new Request(content: '{invalid'),
            $this->createMock(ResetPasswordHelperInterface::class),
            $this->createMock(UserPasswordHasherInterface::class),
        );

        self::assertSame(400, $response->getStatusCode());
    }

    public function testRejectsTooShortPassword(): void
    {
        $response = $this->controller()->reset(
            'token',
            new Request(content: json_encode(['password' => 'short'])),
            $this->createMock(ResetPasswordHelperInterface::class),
            $this->createMock(UserPasswordHasherInterface::class),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testRejectsInvalidToken(): void
    {
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('validateTokenAndFetchUser')->willThrowException(new class('invalid') extends \RuntimeException implements ResetPasswordExceptionInterface {
            public function getReason(): string
            {
                return 'invalid';
            }
        });

        $response = $this->controller()->reset(
            'token',
            new Request(content: json_encode(['password' => 'long-enough'])),
            $helper,
            $this->createMock(UserPasswordHasherInterface::class),
        );

        self::assertSame(400, $response->getStatusCode());
    }

    public function testRejectsTokenForUnknownUser(): void
    {
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('validateTokenAndFetchUser')->with('token')->willReturn(new \stdClass());
        $helper->expects(self::never())->method('removeResetRequest');

        $response = $this->controller()->reset(
            'token',
            new Request(content: json_encode(['password' => 'long-enough'])),
            $helper,
            $this->createMock(UserPasswordHasherInterface::class),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testRejectsMissingPassword(): void
    {
        $response = $this->controller()->reset(
            'token',
            new Request(content: json_encode([])),
            $this->createMock(ResetPasswordHelperInterface::class),
            $this->createMock(UserPasswordHasherInterface::class),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testResetsUserPassword(): void
    {
        $user = new User();
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->expects(self::once())->method('validateTokenAndFetchUser')->with('token')->willReturn($user);
        $helper->expects(self::once())->method('removeResetRequest')->with('token');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())->method('hashPassword')->with($user, 'long-enough')->willReturn('hashed');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller($em)->reset(
            'token',
            new Request(content: json_encode(['password' => 'long-enough'])),
            $helper,
            $hasher,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('hashed', $user->getPassword());
    }

    private function controller(?EntityManagerInterface $em = null): ResetPasswordController
    {
        $controller = new ResetPasswordController($em ?? $this->createMock(EntityManagerInterface::class));
        $controller->setContainer(new ControllerTestContainer([]));

        return $controller;
    }
}
