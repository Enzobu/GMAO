<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final readonly class PasswordResetService
{
    private const REQUEST_MESSAGE = 'Si un compte correspond à cet email, un lien de réinitialisation vient d’être envoyé.';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $parameters,
    ) {}

    /** @return array{message:string} */
    public function request(Request $request, ResetPasswordHelperInterface $resetPasswordHelper, MailerInterface $mailer): array
    {
        $email = $this->email($request);
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'isDeleted' => false,
        ]);

        if (!$user instanceof User) {
            return ['message' => self::REQUEST_MESSAGE];
        }

        try {
            $resetToken = $resetPasswordHelper->generateResetToken($user);
            $mailer->send((new TemplatedEmail())
                ->from(new EmailAddress('no-reply@enzo-palermo.com', 'Enzo PALERMO'))
                ->to((string) $user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->htmlTemplate('reset_password/email.html.twig')
                ->context([
                    'user' => $user,
                    'resetToken' => $resetToken,
                    'frontendResetUrl' => $this->buildFrontendResetUrl($resetToken->getToken()),
                ]));
        } catch (ResetPasswordExceptionInterface) {
            return ['message' => self::REQUEST_MESSAGE];
        }

        return ['message' => self::REQUEST_MESSAGE];
    }

    /** @return array{message:string} */
    public function reset(string $token, Request $request, ResetPasswordHelperInterface $resetPasswordHelper, UserPasswordHasherInterface $passwordHasher): array
    {
        $plainPassword = $this->plainPassword($request);
        $user = $this->validateTokenAndFetchUser($token, $resetPasswordHelper);

        $resetPasswordHelper->removeResetRequest($token);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $this->entityManager->flush();

        return ['message' => 'Votre mot de passe a été réinitialisé.'];
    }

    private function plainPassword(Request $request): string
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON payload');
        }

        $password = (string) ($payload['password'] ?? '');
        if (strlen($password) < 8) {
            throw new UnprocessableEntityHttpException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        return $password;
    }

    private function email(Request $request): string
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON payload');
        }

        $email = trim((string) ($payload['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new UnprocessableEntityHttpException('Email invalide.');
        }

        return $email;
    }

    private function buildFrontendResetUrl(string $token): string
    {
        return rtrim((string) $this->parameters->get('frontend_url'), '/') . '/reset-password/reset/' . rawurlencode($token);
    }

    private function validateTokenAndFetchUser(string $token, ResetPasswordHelperInterface $resetPasswordHelper): User
    {
        try {
            $user = $resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            throw new BadRequestHttpException('Le lien de réinitialisation est invalide ou expiré.');
        }

        if (!$user instanceof User) {
            throw new NotFoundHttpException('Utilisateur introuvable.');
        }

        return $user;
    }
}
