<?php

namespace App\Tests\Unit\Controller;

use App\Entity\PartType;
use App\Entity\Part;
use App\Controller\PartTypeController;
use App\Repository\PartTypeRepository;
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

final class PartTypeControllerTest extends TestCase
{
    public function testIndexRendersPartTypes(): void
    {
        $partTypes = [new PartType()];
        $repository = $this->createMock(PartTypeRepository::class);
        $repository->expects(self::once())->method('findAll')->willReturn($partTypes);
        $twig = $this->twigExpecting('part_type/index.html.twig', static fn (array $parameters): bool => $parameters['part_types'] === $partTypes);

        $response = $this->controller(['twig' => $twig])->index($repository);

        self::assertSame('rendered', $response->getContent());
    }

    public function testShowRendersPartType(): void
    {
        $partType = new PartType();
        $twig = $this->twigExpecting('part_type/show.html.twig', static fn (array $parameters): bool => $parameters['part_type'] === $partType);

        $response = $this->controller(['twig' => $twig])->show($partType);

        self::assertSame('rendered', $response->getContent());
    }

    public function testDeleteWithInvalidCsrfRedirectsWithoutDeleting(): void
    {
        $partType = new PartType();
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->expects(self::once())->method('generate')->with('app_configuration_part_type_index')->willReturn('/types');

        $response = $this->controller(['security.csrf.token_manager' => $csrf, 'router' => $router])
            ->delete(new Request(request: ['_token' => 'invalid']), $partType, $this->createMock(\Doctrine\ORM\EntityManagerInterface::class));

        self::assertSame(302, $response->getStatusCode());
        self::assertFalse($partType->isDeleted());
    }

    public function testNewPersistsValidPartType(): void
    {
        $form = $this->submittedValidForm();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(PartType::class));
        $em->expects(self::once())->method('flush');
        $router = $this->router('app_configuration_part_type_index', '/types');

        $response = $this->controller([
            'form.factory' => $formFactory,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $router,
        ])->new(new Request(), $em);

        self::assertSame(302, $response->getStatusCode());
    }

    public function testNewRendersFormWhenNotSubmitted(): void
    {
        $form = $this->notSubmittedForm();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);
        $twig = $this->twigExpecting('part_type/new.html.twig', static fn (array $parameters): bool => $parameters['part_type'] instanceof PartType && isset($parameters['form']));

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])->new(new Request(), $this->createMock(EntityManagerInterface::class));

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditFlushesValidPartType(): void
    {
        $partType = new PartType();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'form.factory' => $formFactory,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('app_configuration_part_type_index', '/types'),
        ])->edit(new Request(), $partType, $em);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditRedirectsToShowWhenRequested(): void
    {
        $partType = new PartType();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');
        $request = new Request(query: ['show' => 'true']);

        $response = $this->controller([
            'form.factory' => $formFactory,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('app_configuration_part_type_show', '/types/show'),
        ])->edit($request, $partType, $em);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditRendersFormWhenNotSubmitted(): void
    {
        $partType = new PartType();
        $form = $this->notSubmittedForm();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);
        $twig = $this->twigExpecting('part_type/edit.html.twig', static fn (array $parameters): bool => $parameters['part_type'] === $partType && isset($parameters['form']));

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])->edit(new Request(), $partType, $this->createMock(EntityManagerInterface::class));

        self::assertSame('rendered', $response->getContent());
    }

    public function testDeleteWithValidCsrfSoftDeletesPartType(): void
    {
        $partType = new PartType();
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'security.csrf.token_manager' => $csrf,
            'router' => $this->router('app_configuration_part_type_index', '/types'),
            'request_stack' => $this->requestStackWithSession(),
        ])->delete(new Request(request: ['_token' => 'valid']), $partType, $em);

        self::assertSame(302, $response->getStatusCode());
        self::assertTrue($partType->isDeleted());
    }

    public function testDeleteWithLinkedPartsRedirectsWithoutDeleting(): void
    {
        $partType = new PartType();
        $partType->addPart(new Part());
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $response = $this->controller([
            'security.csrf.token_manager' => $csrf,
            'router' => $this->router('app_configuration_part_type_index', '/types'),
            'request_stack' => $this->requestStackWithSession(),
        ])->delete(new Request(request: ['_token' => 'valid']), $partType, $em);

        self::assertSame(302, $response->getStatusCode());
        self::assertFalse($partType->isDeleted());
    }

    /** @param array<string, mixed> $services */
    private function controller(array $services): PartTypeController
    {
        $controller = new PartTypeController();
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
