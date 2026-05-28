<?php

namespace App\Tests\Unit\Controller;

use App\Controller\PartController;
use App\Entity\Document;
use App\Entity\Part;
use App\Entity\PartType;
use App\Repository\DocumentRepository;
use App\Repository\PartRepository;
use App\Repository\PartTypeRepository;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use App\Service\DocumentManager;
use Symfony\Component\String\UnicodeString;
use Twig\Environment;

final class PartControllerTest extends TestCase
{
    public function testIndexRendersFilteredParts(): void
    {
        $parts = [new Part()];
        $partRepository = $this->createMock(PartRepository::class);
        $partRepository->expects(self::once())->method('findByFilters')->with(12, 34)->willReturn($parts);
        $vehicleRepository = $this->createMock(VehicleRepository::class);
        $vehicleRepository->method('findBy')->willReturn([]);
        $partTypeRepository = $this->createMock(PartTypeRepository::class);
        $partTypeRepository->method('findBy')->willReturn([]);
        $twig = $this->twigExpecting('part/index.html.twig', static fn (array $parameters): bool => $parameters['parts'] === $parts && $parameters['selectedVehicleId'] === '12' && $parameters['selectedPartTypeId'] === '34');

        $response = $this->controller(['twig' => $twig], admin: false)
            ->index(new Request(query: ['vehicle' => '12', 'partType' => '34']), $partRepository, $vehicleRepository, $partTypeRepository);

        self::assertSame('rendered', $response->getContent());
    }

    public function testNewPersistsValidPart(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(Part::class));
        $em->expects(self::once())->method('flush');

        $response = $this->controller(['form.factory' => $formFactory, 'router' => $this->router('/parts')])
            ->new(new Request(), $em);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testNewRendersFormWhenNotSubmitted(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $twig = $this->twigExpecting('part/new.html.twig', static fn (array $parameters): bool => $parameters['part'] instanceof Part && isset($parameters['form']));

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->new(new Request(), $this->createMock(EntityManagerInterface::class));

        self::assertSame('rendered', $response->getContent());
    }

    public function testShowRendersPartDocuments(): void
    {
        $part = new Part();
        $documents = [new Document()];
        $documentRepository = $this->createMock(DocumentRepository::class);
        $documentRepository->expects(self::once())->method('findByPart')->with($part, false)->willReturn($documents);
        $twig = $this->twigExpecting('part/show.html.twig', static fn (array $parameters): bool => $parameters['part'] === $part && $parameters['part_document'] === $documents);

        $response = $this->controller(['twig' => $twig], admin: false)->show($part, $documentRepository);

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditFlushesAndRedirectsToIndex(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'form.factory' => $formFactory,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts'),
        ])->edit(new Request(), new Part(), $em);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditRendersFormWhenNotSubmitted(): void
    {
        $part = new Part();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $twig = $this->twigExpecting('part/edit.html.twig', static fn (array $parameters): bool => $parameters['part'] === $part && isset($parameters['form']));

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])->edit(new Request(), $part, $this->createMock(EntityManagerInterface::class));

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditRedirectsWhenUserIsNotAdmin(): void
    {
        $response = $this->controller([
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts'),
        ], admin: false)->edit(new Request(), new Part(), $this->createMock(EntityManagerInterface::class));

        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeleteWithValidCsrfSoftDeletesPart(): void
    {
        $part = new Part();
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller(['security.csrf.token_manager' => $csrf, 'router' => $this->router('/parts')])
            ->delete(new Request(request: ['_token' => 'valid']), $part, $em);

        self::assertSame(303, $response->getStatusCode());
        self::assertTrue($part->isDeleted());
    }

    public function testAddStockRejectsInvalidCsrf(): void
    {
        $part = (new Part())->setQuantity(2);
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);

        $response = $this->controller([
            'security.csrf.token_manager' => $csrf,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts'),
        ])->addStock(new Request(request: ['_token' => 'invalid']), $part, $this->createMock(EntityManagerInterface::class));

        self::assertSame(303, $response->getStatusCode());
        self::assertSame(2, $part->getQuantity());
    }

    public function testAddStockRejectsNonPositiveQuantity(): void
    {
        $part = (new Part())->setQuantity(2);
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);

        $response = $this->controller([
            'security.csrf.token_manager' => $csrf,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts'),
        ])->addStock(new Request(request: ['_token' => 'valid', 'quantity' => '-1']), $part, $this->createMock(EntityManagerInterface::class));

        self::assertSame(303, $response->getStatusCode());
        self::assertSame(2, $part->getQuantity());
    }

    public function testAddStockIncreasesQuantity(): void
    {
        $part = (new Part())->setQuantity(2);
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'security.csrf.token_manager' => $csrf,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts'),
        ])->addStock(new Request(request: ['_token' => 'valid', 'quantity' => '3']), $part, $em);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame(5, $part->getQuantity());
    }

    public function testAddStockRedirectsWhenUserIsNotAdmin(): void
    {
        $response = $this->controller([
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts'),
        ], admin: false)->addStock(new Request(), (new Part())->setQuantity(1), $this->createMock(EntityManagerInterface::class));

        self::assertSame(303, $response->getStatusCode());
    }

    public function testNewRedirectsWhenUserIsNotAdmin(): void
    {
        $response = $this->controller([
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts'),
        ], admin: false)->new(new Request(), $this->createMock(EntityManagerInterface::class));

        self::assertSame(303, $response->getStatusCode());
    }

    public function testShowRedirectsDeletedPart(): void
    {
        $part = (new Part())->setIsDeleted(true);

        $response = $this->controller([
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts'),
        ], admin: false)->show($part, $this->createMock(DocumentRepository::class));

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditRedirectsToShowWhenRequested(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'form.factory' => $formFactory,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts/1'),
        ])->edit(new Request(query: ['show' => 'true']), new Part(), $em);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeleteWithInvalidCsrfDoesNotDeletePart(): void
    {
        $part = new Part();
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $response = $this->controller(['security.csrf.token_manager' => $csrf, 'router' => $this->router('/parts')])
            ->delete(new Request(request: ['_token' => 'invalid']), $part, $em);

        self::assertSame(303, $response->getStatusCode());
        self::assertFalse($part->isDeleted());
    }

    public function testDeleteRedirectsWhenUserIsNotAdmin(): void
    {
        $part = new Part();

        $response = $this->controller([
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts'),
        ], admin: false)->delete(new Request(), $part, $this->createMock(EntityManagerInterface::class));

        self::assertSame(303, $response->getStatusCode());
        self::assertFalse($part->isDeleted());
    }

    public function testNewDocumentRendersWhenNoFileWasUploaded(): void
    {
        $part = $this->partWithType();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidDocumentFormWithoutFile());
        $twig = $this->twigExpecting('document/new.html.twig', static fn (array $parameters): bool => $parameters['document'] instanceof Document && $parameters['entity'] === $part && $parameters['subtitle'] === 'Pièce : Filtre');

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $part, $this->createMock(\Symfony\Component\String\Slugger\SluggerInterface::class));

        self::assertSame('rendered', $response->getContent());
    }

    public function testNewDocumentPersistsUploadedFile(): void
    {
        $part = $this->partWithType();
        $uploadedFile = $this->uploadedFile();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidDocumentFormWithFile($uploadedFile));
        $slugger = $this->createMock(\Symfony\Component\String\Slugger\SluggerInterface::class);
        $slugger->method('slug')->willReturn(new UnicodeString('manual'));
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::callback(static fn (Document $document): bool => $document->getPart() === $part && $document->getOriginalFilename() === 'manual.pdf'));
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'form.factory' => $formFactory,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts/1'),
            'parameter_bag' => $this->parameterBag($this->documentsDirectory()),
        ])->newDocument(new Request(), $em, $part, $slugger);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testNewDocumentRendersWhenUploadMoveFails(): void
    {
        $part = $this->partWithType();
        $uploadedFile = $this->uploadedFile();
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidDocumentFormWithFile($uploadedFile));
        $slugger = $this->createMock(\Symfony\Component\String\Slugger\SluggerInterface::class);
        $slugger->method('slug')->willReturn(new UnicodeString('manual'));
        $twig = $this->twigExpecting('document/new.html.twig', static fn (array $parameters): bool => $parameters['document'] instanceof Document && $parameters['entity'] === $part);

        $response = $this->controller([
            'form.factory' => $formFactory,
            'request_stack' => $this->requestStackWithSession(),
            'twig' => $twig,
            'parameter_bag' => $this->parameterBag('/proc/no-such-directory'),
        ])->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $part, $slugger);

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditDocumentFlushesAndRedirectsWhenChanged(): void
    {
        $part = $this->partWithType();
        $document = (new Document())->setName('Old')->setDescription('Old description');
        $form = $this->submittedValidForm();
        $form->method('handleRequest')->willReturnCallback(static function () use ($form, $document): FormInterface {
            $document->setName('New');

            return $form;
        });
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'form.factory' => $formFactory,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts/1'),
        ])->editDocument(new Request(), $em, $part, $document);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditDocumentFlushesAndWarnsWhenUnchanged(): void
    {
        $part = $this->partWithType();
        $document = (new Document())->setName('Same')->setDescription('Same description');
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller([
            'form.factory' => $formFactory,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts/1'),
        ])->editDocument(new Request(), $em, $part, $document);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditDocumentRendersWhenNotSubmitted(): void
    {
        $part = $this->partWithType();
        $document = (new Document())->setName('Doc');
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $twig = $this->twigExpecting('document/edit.html.twig', static fn (array $parameters): bool => $parameters['document'] === $document && $parameters['entity'] === $part && $parameters['subtitle'] === 'Pièce : Filtre');

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $part, $document);

        self::assertSame('rendered', $response->getContent());
    }

    public function testDeleteDocumentWithValidCsrfSoftDeletesDocument(): void
    {
        $document = (new Document())->setName('Doc');
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);
        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::once())->method('softDelete')->with($document);

        $response = $this->controller([
            'security.csrf.token_manager' => $csrf,
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts/1'),
        ])->deleteDocument(new Request(request: ['_token' => 'valid']), $manager, $this->partWithType(), $document);

        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeleteDocumentWithInvalidCsrfDoesNotDeleteDocument(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(false);
        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::never())->method('softDelete');

        $response = $this->controller([
            'security.csrf.token_manager' => $csrf,
            'router' => $this->router('/parts/1'),
        ])->deleteDocument(new Request(request: ['_token' => 'invalid']), $manager, $this->partWithType(), (new Document())->setName('Doc'));

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditDocumentRedirectsWhenUserIsNotAdmin(): void
    {
        $response = $this->controller([
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts'),
        ], admin: false)->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->partWithType(), (new Document())->setName('Doc'));

        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeleteDocumentRedirectsWhenDocumentIsDeleted(): void
    {
        $document = (new Document())->setName('Doc')->setIsDeleted(true);

        $response = $this->controller([
            'request_stack' => $this->requestStackWithSession(),
            'router' => $this->router('/parts'),
        ])->deleteDocument(new Request(), $this->createMock(DocumentManager::class), $this->partWithType(), $document);

        self::assertSame(303, $response->getStatusCode());
    }

    /** @param array<string, mixed> $services */
    private function controller(array $services, bool $admin = true): PartController
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->with('ROLE_ADMIN')->willReturn($admin);

        $controller = new PartController();
        $controller->setContainer(new ControllerTestContainer($services + [
            'security.authorization_checker' => $authorizationChecker,
            'request_stack' => $this->requestStackWithSession(),
        ]));

        return $controller;
    }

    /** @param callable(array<string, mixed>): bool $parametersMatcher */
    private function twigExpecting(string $template, callable $parametersMatcher): Environment
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())->method('render')->with($template, self::callback($parametersMatcher))->willReturn('rendered');

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

    private function submittedValidDocumentFormWithoutFile(): FormInterface
    {
        $fileField = $this->createMock(FormInterface::class);
        $fileField->method('getData')->willReturn(null);

        $form = $this->submittedValidForm();
        $form->method('get')->with('file')->willReturn($fileField);

        return $form;
    }

    private function submittedValidDocumentFormWithFile(UploadedFile $uploadedFile): FormInterface
    {
        $fileField = $this->createMock(FormInterface::class);
        $fileField->method('getData')->willReturn($uploadedFile);

        $form = $this->submittedValidForm();
        $form->method('get')->with('file')->willReturn($fileField);

        return $form;
    }

    private function partWithType(): Part
    {
        $type = (new PartType())->setName('Filtre');

        return (new Part())->setPartType($type);
    }

    private function uploadedFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'part-document-');
        self::assertIsString($path);
        file_put_contents($path, 'document');

        return new UploadedFile($path, 'manual.pdf', 'application/pdf', null, true);
    }

    private function documentsDirectory(): string
    {
        $directory = sys_get_temp_dir().'/gmao-part-documents';
        if (!is_dir($directory)) {
            mkdir($directory);
        }

        return $directory;
    }

    private function parameterBag(string $documentsDirectory): ContainerBagInterface
    {
        $bag = $this->createMock(ContainerBagInterface::class);
        $bag->method('get')->with('documents_directory')->willReturn($documentsDirectory);

        return $bag;
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
}
