<?php

namespace App\Tests\Unit\Controller\Api;

use App\Controller\Api\MeController;
use App\Entity\User;
use App\Tests\Unit\Controller\ControllerTestContainer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class MeControllerTest extends TestCase
{
    public function testReturnsUnauthenticatedWhenNoUserIsAvailable(): void
    {
        $controller = new MeController();
        $controller->setContainer(new ControllerTestContainer([
            'security.token_storage' => $this->tokenStorage(null),
        ]));

        $response = $controller();

        self::assertSame(401, $response->getStatusCode());
        self::assertSame(['message' => 'Unauthenticated'], json_decode($response->getContent(), true));
    }

    public function testReturnsCurrentUserPayload(): void
    {
        $user = (new User())
            ->setEmail('user@example.com')
            ->setFirstname('Jane')
            ->setLastname('Doe')
            ->setRoles(['ROLE_ADMIN']);
        $controller = new MeController();
        $controller->setContainer(new ControllerTestContainer([
            'security.token_storage' => $this->tokenStorage($user),
        ]));

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
}
