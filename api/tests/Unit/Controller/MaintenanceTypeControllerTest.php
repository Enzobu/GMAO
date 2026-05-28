<?php

namespace App\Tests\Unit\Controller;

use App\Entity\MaintenanceType;
use App\Controller\MaintenanceTypeController;
use App\Repository\MaintenanceTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

final class MaintenanceTypeControllerTest extends TestCase
{
    public function testIndexRendersMaintenanceTypes(): void
    {
        $types = [new MaintenanceType()];
        $repository = $this->createMock(MaintenanceTypeRepository::class);
        $repository->expects(self::once())->method('findBy')->with([], ['name' => 'ASC'])->willReturn($types);
        $twig = $this->twigExpecting('maintenance_type/index.html.twig', static fn (array $parameters): bool => $parameters['maintenance_types'] === $types);

        $response = $this->controller(['twig' => $twig])->index($repository);

        self::assertSame('rendered', $response->getContent());
    }

    public function testShowRendersMaintenanceType(): void
    {
        $type = new MaintenanceType();
        $twig = $this->twigExpecting('maintenance_type/show.html.twig', static fn (array $parameters): bool => $parameters['maintenance_type'] === $type);

        $response = $this->controller(['twig' => $twig])->show($type);

        self::assertSame('rendered', $response->getContent());
    }

    public function testDeleteWithInvalidCsrfRedirectsWithoutDeleting(): void
    {
        $type = new MaintenanceType();
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->expects(self::once())->method('generate')->with('app_configuration_maintenance_type_index')->willReturn('/maintenance-types');

        $response = $this->controller(['security.csrf.token_manager' => $csrf, 'router' => $router])
            ->delete(new Request(request: ['_token' => 'invalid']), $type, $this->createMock(EntityManagerInterface::class));

        self::assertSame(302, $response->getStatusCode());
        self::assertFalse($type->isDeleted());
    }

    public function testNewPersistsValidMaintenanceType(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(MaintenanceType::class));
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'form.factory' => $formFactory,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('app_configuration_maintenance_type_index', '/maintenance-types'),
        ])->new(new Request(), $em);

        self::assertSame(302, $response->getStatusCode());
    }

    public function testNewRendersFormWhenNotSubmitted(): void
    {
        $form = $this->notSubmittedForm();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);
        $twig = $this->twigExpecting('maintenance_type/new.html.twig', static fn (array $parameters): bool => $parameters['maintenance_type'] instanceof MaintenanceType && isset($parameters['form']));

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])->new(new Request(), $this->createMock(EntityManagerInterface::class));

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditFlushesValidMaintenanceType(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'form.factory' => $formFactory,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('app_configuration_maintenance_type_index', '/maintenance-types'),
        ])->edit(new Request(), new MaintenanceType(), $em);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditRedirectsToShowWhenRequested(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');
        $request = new Request(query: ['show' => 'true']);

        $response = $this->controller([
            'form.factory' => $formFactory,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('app_configuration_maintenance_type_show', '/maintenance-types/show'),
        ])->edit($request, new MaintenanceType(), $em);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditRendersFormWhenNotSubmitted(): void
    {
        $type = new MaintenanceType();
        $form = $this->notSubmittedForm();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);
        $twig = $this->twigExpecting('maintenance_type/edit.html.twig', static fn (array $parameters): bool => $parameters['maintenance_type'] === $type && isset($parameters['form']));

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])->edit(new Request(), $type, $this->createMock(EntityManagerInterface::class));

        self::assertSame('rendered', $response->getContent());
    }

    public function testDeleteWithValidCsrfSoftDeletesMaintenanceType(): void
    {
        $type = new MaintenanceType();
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'security.csrf.token_manager' => $csrf,
            'router' => $this->router('app_configuration_maintenance_type_index', '/maintenance-types'),
            'request_stack' => $this->requestStackWithSession(),
        ])->delete(new Request(request: ['_token' => 'valid']), $type, $em);

        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($type->isDeleted());
    }

    /** @param array<string, mixed> $services */
    private function controller(array $services): MaintenanceTypeController
    {
        $controller = new MaintenanceTypeController();
        $controller->setContainer(new ControllerTestContainer($services));

        return $controller;
    }

    /** @param callable(array<string, mixed>): bool $parametersMatcher */
    private function twigExpecting(string $template, callable $parametersMatcher): Environment
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with($template, self::callback($parametersMatcher))
            ->willReturn('rendered');

        return $twig;
    }

    private function submittedValidForm(): FormInterface
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        return $form;
    }

    private function notSubmittedForm(): FormInterface
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);

        return $form;
    }

    private function router(string $route, string $url): UrlGeneratorInterface
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->with($route)->willReturn($url);

        return $router;
    }

    private function requestStackWithSession(): RequestStack
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $stack = new RequestStack();
        $stack->push($request);

        return $stack;
    }
}
