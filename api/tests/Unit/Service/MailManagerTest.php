<?php

namespace App\Tests\Unit\Service;

use App\Service\MailManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Mailer\MailerInterface;

final class MailManagerTest extends TestCase
{
    public function testConstructorReadsMailerSenderParameter(): void
    {
        $params = $this->createMock(ContainerBagInterface::class);
        $params->expects(self::once())->method('get')->with('mailer_sender')->willReturn('sender@example.com');

        $manager = new MailManager($this->createMock(MailerInterface::class), $params);

        self::assertInstanceOf(MailManager::class, $manager);
    }
}
