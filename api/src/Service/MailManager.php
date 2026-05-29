<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Mailer\MailerInterface;

final readonly class MailManager
{
    private String $mailerSender;

    public function __construct(
        private MailerInterface $mailerInterface,
        private ContainerBagInterface $params,
    ) {
        $this->mailerSender = $this->params->get('mailer_sender');
    }

    // public function sendUpdatePasswordMail(
    //     User $user,
    //     String $subject,
    // ): void {
    //     $templateName = 'mail/change_password.html.twig';

    //     $context = [
    //         'user' => $user,
    //     ];

    //     $email = (new TemplatedEmail())
    //         ->from(new Address($this->mailerSender, 'Enzo PALERMO'))
    //         ->to($user->getEmail())
    //         ->subject($subject)
    //         ->htmlTemplate($templateName)
    //         ->context($context)
    //     ;

    //     $envelope = Envelope::create($email);

    //     $this->mailerInterface->send($email, $envelope);
    // }
}
