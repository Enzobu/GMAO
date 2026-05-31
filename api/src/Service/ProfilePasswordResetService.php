<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final readonly class ProfilePasswordResetService
{
    public function __construct(
        private CurrentUserProvider $currentUser,
        private ParameterBagInterface $parameters,
    ) {}

    /** @return array{message:string} */
    public function request(ResetPasswordHelperInterface $resetPasswordHelper, MailerInterface $mailer): array
    {
        $user = $this->currentUser->user();

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
            return ['message' => 'Un email de réinitialisation a déjà été demandé récemment. Vérifiez votre boîte mail.'];
        }

        return ['message' => 'Un email de réinitialisation vous a été envoyé.'];
    }

    private function buildFrontendResetUrl(string $token): string
    {
        return rtrim((string) $this->parameters->get('frontend_url'), '/') . '/reset-password/reset/' . rawurlencode($token);
    }
}
