<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Mailer\MailerInterface;

final readonly class MailManager
{

    public function __construct(
        private ContainerBagInterface $params,
    ) {}
}
