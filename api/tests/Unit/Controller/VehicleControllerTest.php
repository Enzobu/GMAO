<?php

namespace App\Tests\Unit\Controller;

use App\Controller\VehicleController;
use App\Entity\Document;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Repository\DocumentRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\VehicleInspectionRepository;
use App\Repository\VehicleInsuranceRepository;
use App\Repository\VehicleRepository;
use App\Service\DocumentManager;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class VehicleControllerTest extends ControllerUnitTestCase
{
    public function testIndexAndNew(): void
    {
        $vehicles = [$this->vehicle()];
        $repository = $this->createMock(VehicleRepository::class);
        $repository->method('findAllNotDeleted')->willReturn($vehicles);

        $response = $this->controller([
            'twig' => $this->twigExpecting('vehicle/index.html.twig', static fn (array $p): bool => $p['vehicles'] === $vehicles),
        ])->index($repository);
        self::assertSame('rendered', $response->getContent());

        $form = $this->notSubmittedForm();
        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'twig' => $this->twigExpecting('vehicle/new.html.twig', static fn (array $p): bool => $p['vehicle'] instanceof Vehicle && isset($p['form'])),
        ])->new(new Request(), $this->createMock(EntityManagerInterface::class), new User());
        self::assertSame('rendered', $response->getContent());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Vehicle::class));
        $entityManager->expects(self::once())->method('flush');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidForm()),
            'router' => $this->router('/vehicles'),
        ], admin: false)->new(new Request(), $entityManager, new User());
        self::assertSame(303, $response->getStatusCode());
    }

    public function testShowRendersVehicleDetails(): void
    {
        $vehicle = $this->vehicle();
        $insuranceRepository = $this->createMock(VehicleInsuranceRepository::class);
        $insuranceRepository->method('findBy')->willReturn(['insurance']);
        $inspectionRepository = $this->createMock(VehicleInspectionRepository::class);
        $inspectionRepository->method('findBy')->willReturn(['inspection']);
        $documentRepository = $this->createMock(DocumentRepository::class);
        $documentRepository->method('findByVehicle')->willReturn(['document']);
        $maintenanceRepository = $this->createMock(MaintenanceRepository::class);
        $maintenanceRepository->method('findLatestPerformedByVehicle')->willReturn(null);

        $response = $this->controller([
            'twig' => $this->twigExpecting('vehicle/show.html.twig', static fn (array $p): bool => $p['vehicle'] === $vehicle && $p['insurance'] === 'insurance' && $p['inspection'] === 'inspection' && $p['vehicle_document'] === ['document']),
        ])->show($vehicle, $this->vehicleManager(true), $insuranceRepository, $inspectionRepository, new User(), $documentRepository, $maintenanceRepository);

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditRendersSavesAndStopsForMileageWarning(): void
    {
        $vehicle = $this->vehicle()->setLastMileage(100);
        $form = $this->notSubmittedForm();
        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'twig' => $this->twigExpecting('vehicle/edit.html.twig', static fn (array $p): bool => $p['vehicle'] === $vehicle && isset($p['form'])),
        ])->edit(new Request(), $vehicle, $this->createMock(EntityManagerInterface::class), $this->vehicleManager(true), new User());
        self::assertSame('rendered', $response->getContent());

        $manager = $this->vehicleManager(true);
        $manager->method('buildVehicleMileageWarning')->willReturn(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidForm()),
            'router' => $this->router('/vehicle/1'),
        ])->edit(new Request(['show' => 'true']), $vehicle, $entityManager, $manager, new User());
        self::assertSame(303, $response->getStatusCode());

        $manager = $this->vehicleManager(true);
        $manager->method('buildVehicleMileageWarning')->willReturn($this->warning());
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidFormWithLastMileageError()),
            'twig' => $this->twigExpecting('vehicle/edit.html.twig', static fn (array $p): bool => $p['mileage_warning']['fieldError'] === 'Mileage too low'),
        ])->edit(new Request(), $vehicle, $this->createMock(EntityManagerInterface::class), $manager, new User());
        self::assertSame('rendered', $response->getContent());
    }

    public function testDeleteRequiresCsrfAndAdmin(): void
    {
        $vehicle = $this->vehicle();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $response = $this->controller([
            'security.csrf.token_manager' => $this->csrf(true),
            'router' => $this->router('/vehicles'),
        ])->delete(new Request([], ['_token' => 'ok']), $vehicle, $entityManager, $this->vehicleManager(true), new User());
        self::assertTrue($vehicle->isDeleted());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicle/1')], admin: false)
            ->delete(new Request(), $this->vehicle(), $this->createMock(EntityManagerInterface::class), $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());
    }

    public function testDocumentsRenderUploadEditAndDelete(): void
    {
        $vehicle = $this->vehicle();
        $form = $this->documentForm(null, false);
        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'twig' => $this->twigExpecting('document/new.html.twig', static fn (array $p): bool => $p['entity'] === $vehicle),
        ])->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $this->vehicleManager(true), new User());
        self::assertSame('rendered', $response->getContent());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Document::class));
        $entityManager->expects(self::once())->method('flush');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->documentForm($this->uploadedFile())),
            'parameter_bag' => $this->parameterBag($this->documentsDirectory()),
            'router' => $this->router('/vehicle/1'),
        ])->newDocument(new Request(), $entityManager, $vehicle, $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());

        $document = (new Document())->setName('old')->setDescription('old');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->notSubmittedForm()),
            'twig' => $this->twigExpecting('document/edit.html.twig', static fn (array $p): bool => $p['document'] === $document && $p['entity'] === $vehicle),
        ])->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $document, $this->vehicleManager(true), new User());
        self::assertSame('rendered', $response->getContent());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidForm()),
            'router' => $this->router('/vehicle/1'),
        ])->editDocument(new Request(), $entityManager, $vehicle, $document, $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());

        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::once())->method('softDelete')->with($document);
        $response = $this->controller([
            'security.csrf.token_manager' => $this->csrf(true),
            'router' => $this->router('/vehicle/1'),
        ])->deleteDocument(new Request([], ['_token' => 'ok']), $vehicle, $document, $manager, $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());
    }

    public function testDocumentUploadFailureRendersForm(): void
    {
        $vehicle = $this->vehicle();

        $response = $this->controller([
            'form.factory' => $this->formFactory($this->documentForm($this->uploadedFileThrowingMove())),
            'parameter_bag' => $this->parameterBag($this->documentsDirectory()),
            'twig' => $this->twigExpecting('document/new.html.twig', static fn (array $p): bool => $p['entity'] === $vehicle && $p['document'] instanceof Document),
        ])->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $this->vehicleManager(true), new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testDocumentAuthorizationRedirectBranches(): void
    {
        $vehicle = $this->vehicle();
        $document = new Document();

        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $this->vehicleManager(false), new User());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicle/1')], admin: false)
            ->deleteDocument(new Request(), $vehicle, $document, $this->createMock(DocumentManager::class), $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicle/1')])
            ->deleteDocument(new Request(), $vehicle, $document, $this->createMock(DocumentManager::class), $this->vehicleManager(false), new User());
        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditDocumentChangedNameAddsSuccessFlash(): void
    {
        $vehicle = $this->vehicle();
        $document = (new Document())->setName('old')->setDescription('old');
        $form = $this->submittedValidFormHandling(static function () use ($document): void {
            $document->setName('new');
        });

        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'router' => $this->router('/vehicle/1'),
        ])->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $document, $this->vehicleManager(true), new User());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testAdminCanForceMileageWarning(): void
    {
        $vehicle = $this->vehicle()->setLastMileage(100);
        $manager = $this->vehicleManager(true);
        $manager->method('buildVehicleMileageWarning')->willReturn($this->warning());

        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidFormWithLastMileageError(false)),
            'router' => $this->router('/vehicles'),
        ])->edit(new Request([], [VehicleManager::FORCE_MILEAGE_FIELD => '1']), $vehicle, $this->createMock(EntityManagerInterface::class), $manager, new User());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testAuthorizationRedirectBranches(): void
    {
        $vehicle = $this->vehicle()->setIsDeleted(true);
        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->show($vehicle, $this->vehicleManager(true), $this->createMock(VehicleInsuranceRepository::class), $this->createMock(VehicleInspectionRepository::class), new User(), $this->createMock(DocumentRepository::class), $this->createMock(MaintenanceRepository::class));
        self::assertSame(303, $response->getStatusCode());

        $vehicle = $this->vehicle();
        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->edit(new Request(), $vehicle, $this->createMock(EntityManagerInterface::class), $this->vehicleManager(false), new User());
        self::assertSame(303, $response->getStatusCode());

        $document = (new Document())->setIsDeleted(true);
        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $document, $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());
    }

    /** @param array<string, mixed> $services */
    private function controller(array $services = [], bool $admin = true): VehicleController
    {
        return $this->wireController(new VehicleController(), $services, $admin);
    }

    private function vehicleManager(bool $authorized): VehicleManager
    {
        $manager = $this->createMock(VehicleManager::class);
        $manager->method('isAuthorized')->willReturn($authorized);

        return $manager;
    }

    private function vehicle(): Vehicle
    {
        return $this->setEntityId((new Vehicle())->setName('van')->setRegistration('ab-123-cd'), 1);
    }

    /** @return array{currentMileage:int, submittedMileage:int, fieldError:string} */
    private function warning(): array
    {
        return ['currentMileage' => 100, 'submittedMileage' => 90, 'fieldError' => 'Mileage too low'];
    }

    private function submittedValidFormWithLastMileageError(bool $expectError = true): FormInterface
    {
        $mileage = $this->createMock(FormInterface::class);
        $mileage->expects($expectError ? self::once() : self::never())->method('addError');
        $form = $this->submittedValidForm();
        $form->method('get')->with('lastMileage')->willReturn($mileage);

        return $form;
    }

    private function uploadedFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'vehicle-document-');
        self::assertIsString($path);
        file_put_contents($path, 'document');

        return new UploadedFile($path, 'registration.pdf', 'application/pdf', null, true);
    }

    private function documentsDirectory(): string
    {
        $directory = sys_get_temp_dir().'/gmao-vehicle-documents';
        if (!is_dir($directory)) {
            mkdir($directory);
        }

        return $directory;
    }
}
