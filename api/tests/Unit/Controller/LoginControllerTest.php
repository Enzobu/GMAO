<?php

namespace App\Tests\Unit\Controller;

use App\Form\LoginType;
use App\Controller\LoginController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Environment;

final class LoginControllerTest extends TestCase
{
    public function testIndexRendersLoginPageWithLastUsername(): void
    {
        $auth = $this->createMock(AuthenticationUtils::class);
        $auth->method('getLastAuthenticationError')->willReturn(null);
        $auth->method('getLastUsername')->willReturn('user@example.com');
        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $form->method('createView')->willReturn($formView);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects(self::once())
            ->method('create')
            ->with(LoginType::class, ['email' => 'user@example.com'], [])
            ->willReturn($form);
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with('login/index.html.twig', self::callback(static fn (array $parameters): bool => $parameters['last_username'] === 'user@example.com' && $parameters['form'] === $formView))
            ->willReturn('login html');
        $controller = new LoginController();
        $controller->setContainer(new ControllerTestContainer(['form.factory' => $formFactory, 'twig' => $twig]));

        $response = $controller->index($auth);

        self::assertSame('login html', $response->getContent());
    }

    public function testLogoutThrowsLogicException(): void
    {
        $this->expectException(\LogicException::class);

        (new LoginController())->logout();
    }
}
