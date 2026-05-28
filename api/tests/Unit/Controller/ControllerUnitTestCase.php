<?php

namespace App\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

abstract class ControllerUnitTestCase extends TestCase
{
    /** @param array<string, mixed> $services */
    protected function wireController(object $controller, array $services = [], bool $admin = true): object
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->with('ROLE_ADMIN')->willReturn($admin);

        $controller->setContainer(new ControllerTestContainer($services + [
            'security.authorization_checker' => $authorizationChecker,
            'request_stack' => $this->requestStackWithSession(),
        ]));

        return $controller;
    }

    /** @param callable(array<string, mixed>): bool $parametersMatcher */
    protected function twigExpecting(string $template, callable $parametersMatcher): Environment&MockObject
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with($template, self::callback($parametersMatcher))
            ->willReturn('rendered');

        return $twig;
    }

    protected function formFactory(FormInterface $form): FormFactoryInterface&MockObject
    {
        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->method('create')->willReturn($form);

        return $factory;
    }

    protected function submittedValidForm(): FormInterface&MockObject
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        return $form;
    }

    protected function submittedValidFormHandling(callable $callback): FormInterface&MockObject
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnCallback(static function (Request $request) use ($form, $callback): FormInterface {
            $callback($request);

            return $form;
        });
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        return $form;
    }

    protected function notSubmittedForm(): FormInterface&MockObject
    {
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn(new FormView());

        return $form;
    }

    protected function documentForm(?object $uploadedFile = null, bool $submitted = true): FormInterface&MockObject
    {
        $file = $this->createMock(FormInterface::class);
        $file->method('getData')->willReturn($uploadedFile);

        $form = $submitted ? $this->submittedValidForm() : $this->notSubmittedForm();
        $form->method('get')->with('file')->willReturn($file);

        return $form;
    }

    protected function uploadedFileThrowingMove(): UploadedFile&MockObject
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('document.pdf');
        $file->method('guessExtension')->willReturn('pdf');
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getSize')->willReturn(123);
        $file->method('move')->willThrowException(new FileException('move failed'));

        return $file;
    }

    protected function router(string $url = '/redirect'): UrlGeneratorInterface&MockObject
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn($url);

        return $router;
    }

    protected function csrf(bool $valid): CsrfTokenManagerInterface&MockObject
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->with(self::isInstanceOf(CsrfToken::class))->willReturn($valid);

        return $csrf;
    }

    protected function parameterBag(string $documentsDirectory): ContainerBagInterface&MockObject
    {
        $bag = $this->createMock(ContainerBagInterface::class);
        $bag->method('get')->with('documents_directory')->willReturn($documentsDirectory);

        return $bag;
    }

    protected function requestStackWithSession(): RequestStack
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $stack = new RequestStack();
        $stack->push($request);

        return $stack;
    }

    protected function setEntityId(object $entity, int $id): object
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);

        return $entity;
    }
}
