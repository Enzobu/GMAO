<?php

namespace App\Tests\Unit\Controller;

use App\Controller\ProfileController;
use App\Entity\Document;
use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Service\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;
use Twig\Environment;

final class ProfileControllerTest extends TestCase
{
    public function testIndexRendersProfileAndCreatesAddress(): void
    {
        $user = $this->namedUser();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $repository = $this->createMock(DocumentRepository::class);
        $repository->method('findByUser')->with($user, false)->willReturn([]);
        $twig = $this->twigExpecting('profile/index.html.twig', static fn (array $parameters): bool => $parameters['user'] instanceof User && isset($parameters['form']));

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->index($user, new Request(), $this->createMock(EntityManagerInterface::class), $repository);

        self::assertNotNull($user->getAddress());
        self::assertSame('rendered', $response->getContent());
    }

    public function testIndexFlushesWhenProfileChanged(): void
    {
        $user = $this->namedUser();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturnCallback(function () use ($user): FormInterface {
            $user->setFirstname('changed');

            return $this->submittedValidForm();
        });
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/profile')])
            ->index($user, new Request(), $em, $this->createMock(DocumentRepository::class));

        self::assertSame('/profile', $response->headers->get('Location'));
    }

    public function testIndexWarnsWhenNothingChanged(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/profile')])
            ->index($this->namedUser(), new Request(), $em, $this->createMock(DocumentRepository::class));

        self::assertSame(302, $response->getStatusCode());
    }

    public function testNewDocumentRendersInitialForm(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $twig = $this->twigExpecting('document/new.html.twig', static fn (array $parameters): bool => $parameters['entity'] instanceof User);

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->namedUser(), $this->slugger());

        self::assertSame('rendered', $response->getContent());
    }

    public function testNewDocumentPersistsUploadedFile(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->documentForm($this->uploadedFile()));
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(Document::class));
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'form.factory' => $formFactory,
            'parameter_bag' => $this->parameterBag(),
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/profile'),
        ])->newDocument(new Request(), $em, $this->namedUser(), $this->slugger());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testNewDocumentRendersWhenUploadFails(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('manual.pdf');
        $file->method('guessExtension')->willReturn(null);
        $file->method('getClientOriginalExtension')->willReturn('pdf');
        $file->method('getMimeType')->willReturn('application/pdf');
        $file->method('getSize')->willReturn(12);
        $file->method('move')->willThrowException(new FileException());
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->documentForm($file));
        $twig = $this->twigExpecting('document/new.html.twig', static fn (array $parameters): bool => $parameters['entity'] instanceof User);

        $response = $this->controller([
            'form.factory' => $formFactory,
            'parameter_bag' => $this->parameterBag(),
            'request_stack' => $this->requestStackWithSession(),
            'twig' => $twig,
        ])->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->namedUser(), $this->slugger());

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditDocumentFlushesAndRedirectsWithSuccess(): void
    {
        $document = (new Document())->setName('old')->setDescription('old');
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturnCallback(function () use ($document): FormInterface {
            $document->setName('new');

            return $this->submittedValidForm();
        });

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/profile')])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->namedUser(), $document);

        self::assertSame('/profile', $response->headers->get('Location'));
    }

    public function testEditDocumentFlushesAndWarnsWithoutChanges(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/profile')])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->namedUser(), new Document());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditDocumentRendersWhenNotSubmitted(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $twig = $this->twigExpecting('document/edit.html.twig', static fn (array $parameters): bool => $parameters['document'] instanceof Document);

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->namedUser(), new Document());

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditDocumentRedirectsDeletedDocument(): void
    {
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/vehicles')])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->namedUser(), (new Document())->setIsDeleted(true));

        self::assertSame('/vehicles', $response->headers->get('Location'));
    }

    public function testDeleteDocumentSoftDeletesForAdmin(): void
    {
        $document = $this->documentWithId(4);
        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::once())->method('softDelete')->with($document);

        $response = $this->controller(['security.csrf.token_manager' => $this->csrf(true), 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/profile')])
            ->deleteDocument(new Request(request: ['_token' => 'ok']), $manager, $document);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeleteDocumentRedirectsForNonAdmin(): void
    {
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/vehicle')], false)
            ->deleteDocument(new Request(), $this->createMock(DocumentManager::class), new Document());

        self::assertSame('/vehicle', $response->headers->get('Location'));
    }

    public function testDeleteDocumentIgnoresInvalidCsrf(): void
    {
        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::never())->method('softDelete');

        $response = $this->controller(['security.csrf.token_manager' => $this->csrf(false), 'router' => $this->router('/profile')])
            ->deleteDocument(new Request(request: ['_token' => 'bad']), $manager, $this->documentWithId(4));

        self::assertSame('/profile', $response->headers->get('Location'));
    }

    /** @param array<string, mixed> $services */
    private function controller(array $services, bool $admin = true): ProfileController
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->with('ROLE_ADMIN')->willReturn($admin);
        $controller = new ProfileController();
        $controller->setContainer(new ControllerTestContainer($services + ['security.authorization_checker' => $auth]));

        return $controller;
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

    private function documentForm(UploadedFile $file): FormInterface
    {
        $field = $this->createMock(FormInterface::class);
        $field->method('getData')->willReturn($file);
        $form = $this->submittedValidForm();
        $form->method('get')->with('file')->willReturn($field);

        return $form;
    }

    /** @param callable(array<string, mixed>): bool $parametersMatcher */
    private function twigExpecting(string $template, callable $parametersMatcher): Environment
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())->method('render')->with($template, self::callback($parametersMatcher))->willReturn('rendered');

        return $twig;
    }

    private function router(string $url): UrlGeneratorInterface
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn($url);

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

    private function csrf(bool $valid): CsrfTokenManagerInterface
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn($valid);

        return $csrf;
    }

    private function parameterBag(): ContainerBagInterface
    {
        $bag = $this->createMock(ContainerBagInterface::class);
        $bag->method('get')->with('documents_directory')->willReturn(sys_get_temp_dir());

        return $bag;
    }

    private function slugger(): SluggerInterface
    {
        $slugger = $this->createMock(SluggerInterface::class);
        $slugger->method('slug')->willReturn(new UnicodeString('manual'));

        return $slugger;
    }

    private function uploadedFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'profile-document-');
        self::assertIsString($path);
        file_put_contents($path, 'document');

        return new UploadedFile($path, 'manual.pdf', 'application/pdf', null, true);
    }

    private function namedUser(): User
    {
        return (new User())->setFirstname('enzo')->setLastname('palermo')->setEmail('enzo@example.com');
    }

    private function documentWithId(int $id): Document
    {
        $document = new Document();
        $reflection = new \ReflectionProperty(Document::class, 'id');
        $reflection->setValue($document, $id);

        return $document;
    }
}
