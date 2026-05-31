<?php

namespace App\Tests\Unit\Controller\Api;

use App\Controller\Api\MeController;
use App\Entity\User;
use App\Service\CurrentUserProvider;
use App\Service\CurrentUserSerializer;
use App\Tests\Unit\Controller\ControllerTestContainer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class MeControllerTest extends TestCase
{
    public function testReturnsUnauthenticatedWhenNoUserIsAvailable(): void
    {
        $this->assertHttpExceptionStatus(401, fn () => $this->controller(null)());
    }

    public function testReturnsCurrentUserPayload(): void
    {
        $user = (new User())
            ->setEmail('user@example.com')
            ->setFirstname('Jane')
            ->setLastname('Doe')
            ->setRoles(['ROLE_ADMIN']);
        $controller = $this->controller($user);

        $response = $controller();
        $payload = json_decode($response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('user@example.com', $payload['email']);
        self::assertSame('Jane DOE', $payload['displayName']);
        self::assertSame('JD', $payload['initials']);
    }

    private function tokenStorage(?User $user): TokenStorageInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $storage = $this->createMock(TokenStorageInterface::class);
        $storage->method('getToken')->willReturn($token);

        return $storage;
    }

    private function controller(?User $user): MeController
    {
        $controller = new MeController(new CurrentUserProvider($this->tokenStorage($user)), new CurrentUserSerializer());
        $controller->setContainer(new ControllerTestContainer([]));

        return $controller;
    }

    private function assertHttpExceptionStatus(int $statusCode, callable $callback): void
    {
        try {
            $callback();
        } catch (HttpExceptionInterface $exception) {
            self::assertSame($statusCode, $exception->getStatusCode());

            return;
        }

        self::fail(sprintf('Expected HTTP exception with status %d.', $statusCode));
    }
}
