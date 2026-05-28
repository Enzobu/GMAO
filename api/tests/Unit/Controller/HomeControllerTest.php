<?php

namespace App\Tests\Unit\Controller;

use App\Controller\HomeController;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

final class HomeControllerTest extends TestCase
{
    public function testIndexRendersHomePage(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with('home/index.html.twig', ['controller_name' => 'HomeController'])
            ->willReturn('home html');
        $controller = new HomeController();
        $controller->setContainer(new ControllerTestContainer(['twig' => $twig]));

        $response = $controller->index();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('home html', $response->getContent());
    }
}
