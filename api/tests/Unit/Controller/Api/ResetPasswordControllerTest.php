<?php

namespace App\Tests\Unit\Controller\Api;

use App\Controller\Api\ResetPasswordController;
use App\Entity\User;
use App\Service\PasswordResetService;
use App\Tests\Unit\Controller\ControllerTestContainer;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

final class ResetPasswordControllerTest extends TestCase
{
    public function testRejectsInvalidJson(): void
    {
        $this->assertHttpExceptionStatus(400, fn () => $this->controller()->reset(
            'token',
            new Request(content: '{invalid'),
            $this->createMock(ResetPasswordHelperInterface::class),
            $this->createMock(UserPasswordHasherInterface::class),
        ));
    }

    public function testRejectsTooShortPassword(): void
    {
        $this->assertHttpExceptionStatus(422, fn () => $this->controller()->reset(
            'token',
            new Request(content: json_encode(['password' => 'short'])),
            $this->createMock(ResetPasswordHelperInterface::class),
            $this->createMock(UserPasswordHasherInterface::class),
        ));
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

        $this->assertHttpExceptionStatus(400, fn () => $this->controller()->reset(
            'token',
            new Request(content: json_encode(['password' => 'long-enough'])),
            $helper,
            $this->createMock(UserPasswordHasherInterface::class),
        ));
    }

    public function testRejectsTokenForUnknownUser(): void
    {
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('validateTokenAndFetchUser')->with('token')->willReturn(new \stdClass());
        $helper->expects(self::never())->method('removeResetRequest');

        $this->assertHttpExceptionStatus(404, fn () => $this->controller()->reset(
            'token',
            new Request(content: json_encode(['password' => 'long-enough'])),
            $helper,
            $this->createMock(UserPasswordHasherInterface::class),
        ));
    }

    public function testRejectsMissingPassword(): void
    {
        $this->assertHttpExceptionStatus(422, fn () => $this->controller()->reset(
            'token',
            new Request(content: json_encode([])),
            $this->createMock(ResetPasswordHelperInterface::class),
            $this->createMock(UserPasswordHasherInterface::class),
        ));
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

    public function testRequestRejectsInvalidEmail(): void
    {
        $this->assertHttpExceptionStatus(422, fn () => $this->controller()->request(
            new Request(content: json_encode(['email' => 'invalid'])),
            $this->createMock(ResetPasswordHelperInterface::class),
            $this->createMock(MailerInterface::class),
        ));
    }

    public function testRequestRejectsInvalidJson(): void
    {
        $this->assertHttpExceptionStatus(400, fn () => $this->controller()->request(
            new Request(content: '{invalid'),
            $this->createMock(ResetPasswordHelperInterface::class),
            $this->createMock(MailerInterface::class),
        ));
    }

    public function testRequestReturnsGenericMessageForUnknownEmail(): void
    {
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->expects(self::never())->method('generateResetToken');
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $response = $this->controller($this->entityManagerForUser(null))->request(
            new Request(content: json_encode(['email' => 'missing@example.com'])),
            $helper,
            $mailer,
        );

        self::assertSame(200, $response->getStatusCode());
    }

    public function testRequestSendsResetEmail(): void
    {
        $user = (new User())->setEmail('user@example.com')->setFirstname('Jane')->setLastname('Doe');
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->expects(self::once())
            ->method('generateResetToken')
            ->with($user)
            ->willReturn(new ResetPasswordToken('token value', new \DateTimeImmutable('+1 hour'), time()));
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->with(self::callback(function (RawMessage $message): bool {
            self::assertInstanceOf(TemplatedEmail::class, $message);
            self::assertSame('user@example.com', $message->getTo()[0]->getAddress());
            self::assertSame('reset_password/email.html.twig', $message->getHtmlTemplate());
            self::assertSame('https://front.example/reset-password/reset/token%20value', $message->getContext()['frontendResetUrl']);

            return true;
        }));

        $response = $this->controller($this->entityManagerForUser($user))->request(
            new Request(content: json_encode(['email' => 'user@example.com'])),
            $helper,
            $mailer,
        );

        self::assertSame(200, $response->getStatusCode());
    }

    public function testRequestHandlesRecentRequest(): void
    {
        $user = (new User())->setEmail('user@example.com');
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('generateResetToken')->willThrowException(new class('recent') extends \RuntimeException implements ResetPasswordExceptionInterface {
            public function getReason(): string
            {
                return 'recent';
            }
        });
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $response = $this->controller($this->entityManagerForUser($user))->request(
            new Request(content: json_encode(['email' => 'user@example.com'])),
            $helper,
            $mailer,
        );

        self::assertSame(200, $response->getStatusCode());
    }

    private function controller(?EntityManagerInterface $em = null): ResetPasswordController
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->with('frontend_url')->willReturn('https://front.example');
        $controller = new ResetPasswordController(new PasswordResetService(
            $em ?? $this->createMock(EntityManagerInterface::class),
            $parameterBag,
        ));
        $controller->setContainer(new ControllerTestContainer([]));

        return $controller;
    }

    private function entityManagerForUser(?User $user): EntityManagerInterface
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => $user?->getEmail() ?? 'missing@example.com', 'isDeleted' => false])
            ->willReturn($user);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(User::class)->willReturn($repository);

        return $em;
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
