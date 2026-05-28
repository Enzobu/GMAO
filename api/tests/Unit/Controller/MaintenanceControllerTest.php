<?php

namespace App\Tests\Unit\Controller;

use App\Controller\MaintenanceController;
use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\MaintenancePart;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Enum\MaintenanceStatusEnum;
use App\Repository\DocumentRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\MaintenanceTypeRepository;
use App\Repository\VehicleRepository;
use App\Service\DocumentManager;
use App\Service\VehicleManager;
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
use Twig\Environment;

final class MaintenanceControllerTest extends TestCase
{
    public function testIndexRendersFilteredMaintenances(): void
    {
        $type = new \App\Entity\MaintenanceType();
        $typeRepository = $this->createMock(MaintenanceTypeRepository::class);
        $typeRepository->method('find')->with(3)->willReturn($type);
        $typeRepository->method('findAllNotDeleted')->willReturn([]);
        $repository = $this->createMock(MaintenanceRepository::class);
        $repository->expects(self::once())->method('findByFilters')->with(2, $type, MaintenanceStatusEnum::Completed, 'oil', 'mileage', 'ASC')->willReturn([]);
        $vehicleRepository = $this->createMock(VehicleRepository::class);
        $vehicleRepository->method('findAllNotDeleted')->willReturn([]);
        $twig = $this->twigExpecting('maintenance/index.html.twig', static fn (array $parameters): bool => $parameters['selectedVehicleId'] === '2' && $parameters['selectedDirection'] === 'ASC');

        $response = $this->controller(['twig' => $twig])->index(new Request(query: [
            'vehicle' => '2', 'type' => '3', 'status' => MaintenanceStatusEnum::Completed->value, 'q' => 'oil', 'sort' => 'mileage', 'direction' => 'ASC',
        ]), $repository, $typeRepository, $vehicleRepository);

        self::assertSame('rendered', $response->getContent());
    }

    public function testNewRedirectsToIndex(): void
    {
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance')])->new();

        self::assertSame(303, $response->getStatusCode());
    }

    public function testShowRendersDocumentsForOwner(): void
    {
        $user = new User();
        $maintenance = $this->maintenance($user);
        $documents = [new Document()];
        $repository = $this->createMock(DocumentRepository::class);
        $repository->method('findByMaintenance')->with($maintenance, false)->willReturn($documents);
        $twig = $this->twigExpecting('maintenance/show.html.twig', static fn (array $parameters): bool => $parameters['maintenance_document'] === $documents);

        $response = $this->controller(['twig' => $twig], false)->show($maintenance, $repository, $user);

        self::assertSame('rendered', $response->getContent());
    }

    public function testShowRedirectsDeletedMaintenance(): void
    {
        $maintenance = $this->maintenance(new User())->setIsDeleted(true);

        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance')])
            ->show($maintenance, $this->createMock(DocumentRepository::class), new User());

        self::assertSame('/maintenance', $response->headers->get('Location'));
    }

    public function testShowRendersReadOnlyForNonOwner(): void
    {
        $twig = $this->twigExpecting('maintenance/show.html.twig', static fn (array $parameters): bool => $parameters['maintenance'] instanceof Maintenance);

        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'twig' => $twig], false)
            ->show($this->maintenance(new User()), $this->createMock(DocumentRepository::class), new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditRedirectsNonOwnerUpdate(): void
    {
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance')], false)
            ->edit(new Request(), $this->maintenance(new User()), $this->createMock(EntityManagerInterface::class), $this->createMock(VehicleManager::class), new User());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditRedirectsDeletedMaintenance(): void
    {
        $maintenance = $this->maintenance(new User())->setIsDeleted(true);

        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance')])
            ->edit(new Request(), $maintenance, $this->createMock(EntityManagerInterface::class), $this->createMock(VehicleManager::class), new User());

        self::assertSame('/maintenance', $response->headers->get('Location'));
    }

    public function testEditRedirectsOwnerWhenNotAdmin(): void
    {
        $user = new User();
        $maintenance = $this->maintenance($user);

        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance/1')], false)
            ->edit(new Request(), $maintenance, $this->createMock(EntityManagerInterface::class), $this->createMock(VehicleManager::class), $user);

        self::assertSame('/maintenance/1', $response->headers->get('Location'));
    }

    public function testEditRendersFormWhenNotSubmitted(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $twig = $this->twigExpecting('maintenance/edit.html.twig', static fn (array $parameters): bool => $parameters['maintenance'] instanceof Maintenance && isset($parameters['form']));

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->edit(new Request(), $this->maintenance(new User()), $this->createMock(EntityManagerInterface::class), $this->createMock(VehicleManager::class), new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditStopsForMileageWarning(): void
    {
        $form = $this->submittedValidForm();
        $mileageField = $this->createMock(FormInterface::class);
        $mileageField->expects(self::once())->method('addError');
        $form->method('get')->with('mileage')->willReturn($mileageField);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($form);
        $manager = $this->createMock(VehicleManager::class);
        $manager->method('buildEventMileageWarning')->willReturn(['fieldError' => 'bad']);
        $twig = $this->twigExpecting('maintenance/edit.html.twig', static fn (array $parameters): bool => $parameters['mileage_warning'] === ['fieldError' => 'bad']);

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->edit(new Request(), $this->maintenance(new User()), $this->createMock(EntityManagerInterface::class), $manager, new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditFlushesAndRedirectsToShowWhenForced(): void
    {
        $maintenance = $this->maintenance(new User())->setFinishedAt(new \DateTimeImmutable())->setMileage(100);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $manager = $this->createMock(VehicleManager::class);
        $manager->method('buildEventMileageWarning')->willReturn(['fieldError' => 'bad']);
        $manager->method('syncAfterEventMileageChange')->willReturn(true);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))->method('flush');

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance/1')])
            ->edit(new Request(query: ['show' => 'true'], request: [VehicleManager::FORCE_MILEAGE_FIELD => '1']), $maintenance, $em, $manager, new User());

        self::assertSame('/maintenance/1', $response->headers->get('Location'));
    }

    public function testEditFlushesWithoutMileageWarningAndMaintenanceParts(): void
    {
        $maintenance = $this->maintenance(new User());
        $part = new MaintenancePart();
        $maintenance->addMaintenancePart($part);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());
        $manager = $this->createMock(VehicleManager::class);
        $manager->method('buildEventMileageWarning')->willReturn(null);
        $manager->method('syncAfterEventMileageChange')->willReturn(false);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance')])
            ->edit(new Request(), $maintenance, $em, $manager, new User());

        self::assertSame($maintenance, $part->getMaintenance());
        self::assertSame('/maintenance', $response->headers->get('Location'));
    }

    public function testDeleteSoftDeletesWithValidCsrf(): void
    {
        $maintenance = $this->maintenance(new User())->setFinishedAt(new \DateTimeImmutable())->setMileage(10);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))->method('flush');
        $manager = $this->createMock(VehicleManager::class);
        $manager->method('syncAfterEventMileageChange')->willReturn(true);

        $response = $this->controller(['security.csrf.token_manager' => $this->csrf(true), 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance')])
            ->delete(new Request(request: ['_token' => 'ok']), $maintenance, $em, $manager, new User());

        self::assertTrue($maintenance->isDeleted());
        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeleteRedirectsForNonAdmin(): void
    {
        $user = new User();
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance/1')], false)
            ->delete(new Request(), $this->maintenance($user), $this->createMock(EntityManagerInterface::class), $this->createMock(VehicleManager::class), $user);

        self::assertSame('/maintenance/1', $response->headers->get('Location'));
    }

    public function testDeleteIgnoresInvalidCsrf(): void
    {
        $maintenance = $this->maintenance(new User());
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $this->controller(['security.csrf.token_manager' => $this->csrf(false), 'router' => $this->router('/maintenance')])
            ->delete(new Request(request: ['_token' => 'bad']), $maintenance, $em, $this->createMock(VehicleManager::class), new User());

        self::assertFalse($maintenance->isDeleted());
    }

    public function testNewDocumentRendersInitialForm(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $twig = $this->twigExpecting('document/new.html.twig', static fn (array $parameters): bool => $parameters['entity'] instanceof Maintenance);

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->maintenance(new User()), new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testNewDocumentRedirectsForUnauthorizedUser(): void
    {
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance')], false)
            ->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->maintenance($this->userWithId(1)), $this->userWithId(2));

        self::assertSame('/maintenance', $response->headers->get('Location'));
    }

    public function testNewDocumentPersistsUploadedFile(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->documentForm($this->uploadedFile()));
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(Document::class));

        $response = $this->controller(['form.factory' => $formFactory, 'parameter_bag' => $this->parameterBag(), 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance/1')])
            ->newDocument(new Request(), $em, $this->maintenance(new User()), new User());

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
        $twig = $this->twigExpecting('document/new.html.twig', static fn (array $parameters): bool => $parameters['entity'] instanceof Maintenance);

        $response = $this->controller(['form.factory' => $formFactory, 'parameter_bag' => $this->parameterBag(), 'request_stack' => $this->requestStackWithSession(), 'twig' => $twig])
            ->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->maintenance(new User()), new User());

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

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance/1')])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->maintenance(new User()), $document, new User());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditDocumentRedirectsForUnauthorizedUser(): void
    {
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance')], false)
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->maintenance($this->userWithId(1)), new Document(), $this->userWithId(2));

        self::assertSame('/maintenance', $response->headers->get('Location'));
    }

    public function testDeleteDocumentRedirectsForNonAdminOwner(): void
    {
        $user = new User();
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance/1')], false)
            ->deleteDocument(new Request(), $this->maintenance($user), new Document(), $this->createMock(DocumentManager::class), $user);

        self::assertSame('/maintenance/1', $response->headers->get('Location'));
    }

    public function testEditDocumentRendersWhenNotSubmitted(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->notSubmittedForm());
        $twig = $this->twigExpecting('document/edit.html.twig', static fn (array $parameters): bool => $parameters['document'] instanceof Document);

        $response = $this->controller(['form.factory' => $formFactory, 'twig' => $twig])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->maintenance(new User()), new Document(), new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditDocumentWarnsWhenSubmittedWithoutChanges(): void
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturn($this->submittedValidForm());

        $response = $this->controller(['form.factory' => $formFactory, 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance/1')])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $this->maintenance(new User()), new Document(), new User());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeleteDocumentSoftDeletesForAdmin(): void
    {
        $document = $this->documentWithId(8);
        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::once())->method('softDelete')->with($document);

        $response = $this->controller(['security.csrf.token_manager' => $this->csrf(true), 'request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance/1')])
            ->deleteDocument(new Request(request: ['_token' => 'ok']), $this->maintenance(new User()), $document, $manager, new User());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeleteDocumentRedirectsWhenDocumentDeleted(): void
    {
        $response = $this->controller(['request_stack' => $this->requestStackWithSession(), 'router' => $this->router('/maintenance')])
            ->deleteDocument(new Request(), $this->maintenance(new User()), (new Document())->setIsDeleted(true), $this->createMock(DocumentManager::class), new User());

        self::assertSame('/maintenance', $response->headers->get('Location'));
    }

    /** @param array<string, mixed> $services */
    private function controller(array $services, bool $admin = true): MaintenanceController
    {
        $auth = $this->createMock(AuthorizationCheckerInterface::class);
        $auth->method('isGranted')->with('ROLE_ADMIN')->willReturn($admin);
        $controller = new MaintenanceController();
        $controller->setContainer(new ControllerTestContainer($services + ['security.authorization_checker' => $auth]));

        return $controller;
    }

    private function maintenance(User $owner): Maintenance
    {
        $vehicle = (new Vehicle())->setName('clio')->setRegistration('aa-123-aa')->setUser($owner);

        return (new Maintenance())->setVehicle($vehicle);
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

    private function uploadedFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'maintenance-document-');
        self::assertIsString($path);
        file_put_contents($path, 'document');

        return new UploadedFile($path, 'manual.pdf', 'application/pdf', null, true);
    }

    private function documentWithId(int $id): Document
    {
        $document = new Document();
        $reflection = new \ReflectionProperty(Document::class, 'id');
        $reflection->setValue($document, $id);

        return $document;
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);

        return $user;
    }
}
