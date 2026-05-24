<?php

namespace App\ApiPlatform\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * @implements ProcessorInterface<User, User|null>
 */
final readonly class UserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private MailerInterface $mailer,
        private string $frontendUrl,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?User
    {
        if (!$data instanceof User) {
            return null;
        }

        if ($operation instanceof DeleteOperationInterface) {
            if ($this->security->getUser() === $data) {
                throw new ConflictHttpException('Vous ne pouvez pas supprimer votre propre compte administrateur.');
            }

            $data->setIsDeleted(true);
            $this->entityManager->flush();

            return null;
        }

        $isNew = $data->getId() === null;

        if ($isNew) {
            $this->entityManager->persist($data);
        }

        $this->entityManager->flush();

        if ($isNew) {
            $this->sendPasswordDefinitionEmail($data);
        }

        return $data;
    }

    private function sendPasswordDefinitionEmail(User $user): void
    {
        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(new EmailAddress('no-reply@enzo-palermo.com', 'Enzo PALERMO'))
            ->to((string) $user->getEmail())
            ->subject('Définissez votre mot de passe')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'user' => $user,
                'resetToken' => $resetToken,
                'frontendResetUrl' => rtrim($this->frontendUrl, '/') . '/reset-password/reset/' . rawurlencode($resetToken->getToken()),
            ]);

        $this->mailer->send($email);
    }
}
