<?php

namespace App\Tests\Unit\ApiPlatform\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\ApiPlatform\State\UserStateProcessor;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final class UserStateProcessorTest extends TestCase
{
    public function testReturnsNullForUnsupportedData(): void
    {
        self::assertNull($this->processor()->process(new \stdClass(), new Post()));
    }

    public function testCreatesUserAndSendsPasswordDefinitionEmail(): void
    {
        $user = (new User())->setEmail('user@example.com');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($user);
        $em->expects(self::once())->method('flush');
        $resetHelper = $this->createMock(ResetPasswordHelperInterface::class);
        $resetHelper->expects(self::once())
            ->method('generateResetToken')
            ->with($user)
            ->willReturn(new ResetPasswordToken('token value', new \DateTimeImmutable('+1 hour'), time()));
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (TemplatedEmail $email): bool {
                $context = $email->getContext();

                return $email->getSubject() === 'Définissez votre mot de passe'
                    && $context['frontendResetUrl'] === 'https://front.test/reset-password/reset/token%20value';
            }));

        $result = $this->processor($em, resetHelper: $resetHelper, mailer: $mailer)->process($user, new Post());

        self::assertSame($user, $result);
    }

    public function testUpdatesExistingUserWithoutSendingPasswordEmail(): void
    {
        $user = new User();
        $this->setId($user, 42);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');
        $em->expects(self::once())->method('flush');
        $resetHelper = $this->createMock(ResetPasswordHelperInterface::class);
        $resetHelper->expects(self::never())->method('generateResetToken');
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $result = $this->processor($em, resetHelper: $resetHelper, mailer: $mailer)->process($user, new Post());

        self::assertSame($user, $result);
    }

    public function testCreateUserSkipsEmailWhenResetTokenGenerationFails(): void
    {
        $user = (new User())->setEmail('user@example.com');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($user);
        $em->expects(self::once())->method('flush');
        $resetHelper = $this->createMock(ResetPasswordHelperInterface::class);
        $resetHelper->expects(self::once())
            ->method('generateResetToken')
            ->with($user)
            ->willThrowException(new class extends \RuntimeException implements ResetPasswordExceptionInterface {
                public function getReason(): string
                {
                    return 'failed';
                }
            });
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');

        $result = $this->processor($em, resetHelper: $resetHelper, mailer: $mailer)->process($user, new Post());

        self::assertSame($user, $result);
    }

    public function testDeleteSoftDeletesUser(): void
    {
        $user = new User();
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new User());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $result = $this->processor($em, $security)->process($user, new Delete());

        self::assertNull($result);
        self::assertTrue($user->isDeleted());
    }

    public function testDeleteCurrentUserThrowsConflict(): void
    {
        $user = new User();
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $this->expectException(ConflictHttpException::class);

        $this->processor(security: $security)->process($user, new Delete());
    }

    private function processor(
        ?EntityManagerInterface $em = null,
        ?Security $security = null,
        ?ResetPasswordHelperInterface $resetHelper = null,
        ?MailerInterface $mailer = null,
    ): UserStateProcessor {
        return new UserStateProcessor(
            $em ?? $this->createMock(EntityManagerInterface::class),
            $security ?? $this->createMock(Security::class),
            $resetHelper ?? $this->createMock(ResetPasswordHelperInterface::class),
            $mailer ?? $this->createMock(MailerInterface::class),
            'https://front.test',
        );
    }

    private function setId(User $user, int $id): void
    {
        $property = new \ReflectionProperty($user, 'id');
        $property->setValue($user, $id);
    }
}
