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
}
