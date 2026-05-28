<?php

namespace App\Tests\Unit\Controller;

use App\Controller\UserController;
use App\Entity\Document;
use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Repository\UserRepository;
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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Twig\Environment;

final class UserControllerTest extends TestCase
{
    public function testIndexRendersUsersForAdmin(): void
    {
        $users = [new User()];
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findBy')->with(['isDeleted' => false])->willReturn($users);
        $twig = $this->twigExpecting('user/index.html.twig', static fn (array $parameters): bool => $parameters['users'] === $users);

        $response = $this->controller(['twig' => $twig])->index($repository);

        self::assertSame('rendered', $response->getContent());
    }

    public function testIndexRendersUsersForNonAdminAfterReadOnlyWarning(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findBy')->willReturn([]);
        $twig = $this->twigExpecting('user/index.html.twig', static fn (array $parameters): bool => $parameters['users'] === []);

        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'twig' => $twig], false)->index($repository);

        self::assertSame('rendered', $response->getContent());
    }

    public function testNewPersistsUserAndSendsResetMail(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturnCallback(function (string $type, User $user): FormInterface {
            $user->setEmail('new@example.com');

            return $this->submittedValidForm();
        });
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(User::class));
        $em->expects(self::once())->method('flush');
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('generateResetToken')->willReturn(new ResetPasswordToken('token', new \DateTimeImmutable('+1 hour'), time()));
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/users')])
            ->new(new Request(), $em, $helper, $mailer);

        self::assertSame('/users', $response->headers->get('Location'));
    }

    public function testNewWarnsWhenResetMailFails(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('generateResetToken')->willThrowException($this->resetPasswordException());

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/users')])
            ->new(new Request(), $this->createMock(EntityManagerInterface::class), $helper, $this->createMock(MailerInterface::class));

        self::assertSame(303, $response->getStatusCode());
    }

    public function testNewRendersFormWhenNotSubmitted(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $twig = $this->twigExpecting('user/new.html.twig', static fn (array $parameters): bool => $parameters['user'] instanceof User && isset($parameters['form']));

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->new(new Request(), $this->createMock(EntityManagerInterface::class), $this->createMock(ResetPasswordHelperInterface::class), $this->createMock(MailerInterface::class));

        self::assertSame('rendered', $response->getContent());
    }

    public function testShowRendersUserDocuments(): void
    {
        $user = $this->userWithId(5);
        $documents = [new Document()];
        $repository = $this->createMock(DocumentRepository::class);
        $repository->method('findByUser')->with($user, false)->willReturn($documents);
        $twig = $this->twigExpecting('user/show.html.twig', static fn (array $parameters): bool => $parameters['user'] === $user && $parameters['user_document'] === $documents);

        $response = $this->controller(['twig' => $twig])->show($user, $repository);

        self::assertSame('rendered', $response->getContent());
    }

    public function testShowRedirectsDeletedUser(): void
    {
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/users')])
            ->show((new User())->setIsDeleted(true), $this->createMock(DocumentRepository::class));

        self::assertSame('/users', $response->headers->get('Location'));
    }

    public function testEditFlushesValidForm(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/users')])
            ->edit(new Request(), new User(), $em);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditRendersFormAndCreatesMissingAddress(): void
    {
        $user = new User();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $twig = $this->twigExpecting('user/edit.html.twig', static fn (array $parameters): bool => $parameters['user'] instanceof User && isset($parameters['form']));

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->edit(new Request(), $user, $this->createMock(EntityManagerInterface::class));

        self::assertNotNull($user->getAddress());
        self::assertSame('rendered', $response->getContent());
    }

    public function testEditRedirectsForNonAdmin(): void
    {
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/users')], false)
            ->edit(new Request(), new User(), $this->createMock(EntityManagerInterface::class));

        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeleteSoftDeletesWithValidCsrf(): void
    {
        $user = $this->userWithId(7);
        $csrf = $this->csrf(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $this->controller(['security.csrf.token_manager' => $csrf, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/users')])
            ->delete(new Request(request: ['_token' => 'ok']), $user, $em);

        self::assertTrue($user->isDeleted());
    }

    public function testDeleteRedirectsForNonAdmin(): void
    {
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/users')], false)
            ->delete(new Request(), new User(), $this->createMock(EntityManagerInterface::class));

        self::assertSame('/users', $response->headers->get('Location'));
    }

    public function testDeleteIgnoresInvalidCsrf(): void
    {
        $user = $this->userWithId(7);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $this->controller(['security.csrf.token_manager' => $this->csrf(false), 'router' => $this->router('/users')])
            ->delete(new Request(request: ['_token' => 'bad']), $user, $em);

        self::assertFalse($user->isDeleted());
    }

    public function testNewDocumentRendersInitialForm(): void
    {
        $user = $this->namedUser();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $twig = $this->twigExpecting('document/new.html.twig', static fn (array $parameters): bool => $parameters['entity'] instanceof User);

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $user, $this->slugger());

        self::assertSame('rendered', $response->getContent());
    }

    public function testNewDocumentPersistsUploadedFile(): void
    {
        $file = $this->uploadedFile();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->documentForm($file));
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(Document::class));
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'form.factory' => $formFactory,
            'parameter_bag' => $this->parameterBag(),
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/users/1'),
        ])->newDocument(new Request(), $em, $this->namedUser(), $this->slugger());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testNewDocumentRendersWhenUploadFails(): void
    {
        $file = $this->createMock(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('manual.pdf');
        $file->method('guessExtension')->willReturn('pdf');
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

    public function testEditDocumentFlushesAndRedirects(): void
    {
        $document = (new Document())->setName('old')->setDescription('old');
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturnCallback(function () use ($document): FormInterface {
            $document->setName('new');

            return $this->submittedValidForm();
        });

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/users/1')])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->namedUser(), $document);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditDocumentRedirectsForNonAdmin(): void
    {
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/users')], false)
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->namedUser(), new Document());

        self::assertSame('/users', $response->headers->get('Location'));
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

    public function testEditDocumentWarnsWhenSubmittedWithoutChanges(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/users/1')])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->namedUser(), new Document());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeleteDocumentSoftDeletesWithValidCsrf(): void
    {
        $document = $this->documentWithId(9);
        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::once())->method('softDelete')->with($document);

        $response = $this->controller(['security.csrf.token_manager' => $this->csrf(true), 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/users/1')])
            ->deleteDocument(new Request(request: ['_token' => 'ok']), $manager, $this->namedUser(), $document);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeleteDocumentRedirectsWhenDocumentDeleted(): void
    {
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/users')])
            ->deleteDocument(new Request(), $this->createMock(DocumentManager::class), $this->namedUser(), (new Document())->setIsDeleted(true));

        self::assertSame('/users', $response->headers->get('Location'));
    }

    /** @param array<string, mixed> $services */
    private function controller(array $services, bool $admin = true): UserController
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->with('ROLE_ADMIN')->willReturn($admin);
        $controller = new UserController();
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
        $path = tempnam(sys_get_temp_dir(), 'user-document-');
        self::assertIsString($path);
        file_put_contents($path, 'document');

        return new UploadedFile($path, 'manual.pdf', 'application/pdf', null, true);
    }

    private function namedUser(): User
    {
        return $this->userWithId(1)->setFirstname('enzo')->setLastname('palermo')->setEmail('enzo@example.com');
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }

    private function documentWithId(int $id): Document
    {
        $document = new Document();
        $reflection = new \ReflectionProperty(Document::class, 'id');
        $reflection->setValue($document, $id);

        return $document;
    }

    private function resetPasswordException(): ResetPasswordExceptionInterface
    {
        return new class extends \RuntimeException implements ResetPasswordExceptionInterface {
            public function getReason(): string
            {
                return 'failed';
            }
        };
    }
}
