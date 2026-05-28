<?php

namespace App\Tests\Unit\Controller;

use App\Controller\VehicleInspectionController;
use App\Entity\Document;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Repository\DocumentRepository;
use App\Repository\VehicleInspectionRepository;
use App\Service\DocumentManager;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class VehicleInspectionControllerTest extends ControllerUnitTestCase
{
    public function testIndexRendersInspections(): void
    {
        $vehicle = $this->vehicle();
        $inspections = [$this->inspection($vehicle)];
        $repository = $this->createMock(VehicleInspectionRepository::class);
        $repository->method('findByVehicle')->willReturn($inspections);

        $response = $this->controller([
            'twig' => $this->twigExpecting('vehicle_inspection/index.html.twig', static fn (array $p): bool => $p['vehicle'] === $vehicle && $p['vehicle_inspections'] === $inspections),
        ])->index($repository, $vehicle, $this->vehicleManager(true), new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testNewRendersPersistsAndStopsForMileageWarning(): void
    {
        $vehicle = $this->vehicle();
        $form = $this->notSubmittedForm();
        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'twig' => $this->twigExpecting('vehicle_inspection/new.html.twig', static fn (array $p): bool => $p['vehicle'] === $vehicle && $p['vehicle_inspection'] instanceof VehicleInspection),
        ])->new(new Request(), $this->createMock(EntityManagerInterface::class), $this->vehicleManager(true), new User(), $vehicle);
        self::assertSame('rendered', $response->getContent());

        $manager = $this->vehicleManager(true);
        $manager->method('buildEventMileageWarning')->willReturn(null);
        $manager->method('syncAfterEventMileageChange')->willReturn(true);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(VehicleInspection::class));
        $entityManager->expects(self::exactly(2))->method('flush');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidForm()),
            'router' => $this->router('/inspection'),
        ])->new(new Request(), $entityManager, $manager, new User(), $vehicle);
        self::assertSame(303, $response->getStatusCode());

        $manager = $this->vehicleManager(true);
        $manager->method('buildEventMileageWarning')->willReturn($this->warning());
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidFormWithMileageError()),
            'twig' => $this->twigExpecting('vehicle_inspection/new.html.twig', static fn (array $p): bool => $p['mileage_warning']['fieldError'] === 'Mileage too low'),
        ])->new(new Request(), $this->createMock(EntityManagerInterface::class), $manager, new User(), $vehicle);
        self::assertSame('rendered', $response->getContent());
    }

    public function testShowEditDelete(): void
    {
        $vehicle = $this->vehicle();
        $inspection = $this->inspection($vehicle)->setMileage(120);
        $documents = [new Document()];
        $documentRepository = $this->createMock(DocumentRepository::class);
        $documentRepository->method('findByVehicleInspection')->willReturn($documents);

        $response = $this->controller([
            'twig' => $this->twigExpecting('vehicle_inspection/show.html.twig', static fn (array $p): bool => $p['vehicle_inspection'] === $inspection && $p['vehicle_inspection_document'] === $documents),
        ])->show($inspection, $this->vehicleManager(true), new User(), $vehicle, $documentRepository);
        self::assertSame('rendered', $response->getContent());

        $form = $this->notSubmittedForm();
        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'twig' => $this->twigExpecting('vehicle_inspection/edit.html.twig', static fn (array $p): bool => $p['vehicle_inspection'] === $inspection && isset($p['form'])),
        ])->edit(new Request(), $inspection, $this->vehicleManager(true), $this->createMock(EntityManagerInterface::class), new User(), $vehicle);
        self::assertSame('rendered', $response->getContent());

        $manager = $this->vehicleManager(true);
        $manager->method('buildEventMileageWarning')->willReturn(null);
        $manager->method('syncAfterEventMileageChange')->willReturn(true);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))->method('flush');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidForm()),
            'router' => $this->router('/inspection/show'),
        ])->edit(new Request(['show' => 'true']), $inspection, $manager, $entityManager, new User(), $vehicle);
        self::assertSame(303, $response->getStatusCode());

        $manager = $this->vehicleManager(true);
        $manager->method('syncAfterEventMileageChange')->willReturn(true);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))->method('flush');
        $response = $this->controller([
            'security.csrf.token_manager' => $this->csrf(true),
            'router' => $this->router('/inspection'),
        ])->delete(new Request([], ['_token' => 'ok']), $inspection, $entityManager, $vehicle, $manager, new User());
        self::assertTrue($inspection->isDeleted());
        self::assertSame(303, $response->getStatusCode());
    }

    public function testDocumentsRenderUploadEditAndDelete(): void
    {
        $vehicle = $this->vehicle();
        $inspection = $this->inspection($vehicle);
        $form = $this->documentForm(null, false);
        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'twig' => $this->twigExpecting('document/new.html.twig', static fn (array $p): bool => $p['entity'] === $inspection && isset($p['form'])),
        ])->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $inspection, new AsciiSlugger(), $this->vehicleManager(true), new User());
        self::assertSame('rendered', $response->getContent());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Document::class));
        $entityManager->expects(self::once())->method('flush');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->documentForm($this->uploadedFile())),
            'parameter_bag' => $this->parameterBag($this->documentsDirectory()),
            'router' => $this->router('/inspection/show'),
        ])->newDocument(new Request(), $entityManager, $vehicle, $inspection, new AsciiSlugger(), $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());

        $document = (new Document())->setName('old');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->notSubmittedForm()),
            'twig' => $this->twigExpecting('document/edit.html.twig', static fn (array $p): bool => $p['document'] === $document && $p['entity'] === $inspection),
        ])->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $inspection, $document, $this->vehicleManager(true), new User());
        self::assertSame('rendered', $response->getContent());

        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::once())->method('softDelete')->with($document);
        $response = $this->controller([
            'security.csrf.token_manager' => $this->csrf(true),
            'router' => $this->router('/inspection/show'),
        ])->deleteDocument(new Request([], ['_token' => 'ok']), $vehicle, $inspection, $document, $this->vehicleManager(true), new User(), $manager);
        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditStopsForMileageWarningAndAdminCanForce(): void
    {
        $vehicle = $this->vehicle();
        $inspection = $this->inspection($vehicle)->setMileage(90);
        $manager = $this->vehicleManager(true);
        $manager->method('buildEventMileageWarning')->willReturn($this->warning());

        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidFormWithMileageError()),
            'twig' => $this->twigExpecting('vehicle_inspection/edit.html.twig', static fn (array $p): bool => $p['vehicle_inspection'] === $inspection && $p['mileage_warning']['fieldError'] === 'Mileage too low'),
        ])->edit(new Request(), $inspection, $manager, $this->createMock(EntityManagerInterface::class), new User(), $vehicle);
        self::assertSame('rendered', $response->getContent());

        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidFormWithMileageError(false)),
            'router' => $this->router('/inspection'),
        ])->edit(new Request([], [VehicleManager::FORCE_MILEAGE_FIELD => '1']), $inspection, $manager, $this->createMock(EntityManagerInterface::class), new User(), $vehicle);
        self::assertSame(303, $response->getStatusCode());
    }

    public function testDocumentUploadFailureRendersForm(): void
    {
        $vehicle = $this->vehicle();
        $inspection = $this->inspection($vehicle);

        $response = $this->controller([
            'form.factory' => $this->formFactory($this->documentForm($this->uploadedFileThrowingMove())),
            'parameter_bag' => $this->parameterBag($this->documentsDirectory()),
            'twig' => $this->twigExpecting('document/new.html.twig', static fn (array $p): bool => $p['entity'] === $inspection && $p['document'] instanceof Document),
        ])->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $inspection, new AsciiSlugger(), $this->vehicleManager(true), new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditDocumentSubmittedBranches(): void
    {
        $vehicle = $this->vehicle();
        $inspection = $this->inspection($vehicle);
        $document = (new Document())->setName('old')->setDescription('old');
        $form = $this->submittedValidFormHandling(static function () use ($document): void {
            $document->setName('new');
        });

        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'router' => $this->router('/inspection/show'),
        ])->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $inspection, $document, $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidForm()),
            'router' => $this->router('/inspection/show'),
        ])->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $inspection, $document, $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());
    }

    public function testAuthorizationRedirectBranchesForEditDeleteAndDocuments(): void
    {
        $vehicle = $this->vehicle();
        $inspection = $this->inspection($vehicle);
        $document = new Document();

        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->edit(new Request(), $inspection, $this->vehicleManager(false), $this->createMock(EntityManagerInterface::class), new User(), $vehicle);
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicle/1')], admin: false)
            ->delete(new Request(), $inspection, $this->createMock(EntityManagerInterface::class), $vehicle, $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $inspection, new AsciiSlugger(), $this->vehicleManager(false), new User());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $inspection, $document, $this->vehicleManager(false), new User());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/inspection')])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $inspection, (new Document())->setIsDeleted(true), $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicle/1')], admin: false)
            ->deleteDocument(new Request(), $vehicle, $inspection, $document, $this->vehicleManager(true), new User(), $this->createMock(DocumentManager::class));
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/inspection')])
            ->deleteDocument(new Request(), $vehicle, $inspection, $document, $this->vehicleManager(false), new User(), $this->createMock(DocumentManager::class));
        self::assertSame(303, $response->getStatusCode());
    }

    public function testAuthorizationRedirectBranches(): void
    {
        $vehicle = $this->vehicle()->setIsDeleted(true);
        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->index($this->createMock(VehicleInspectionRepository::class), $vehicle, $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());

        $vehicle = $this->vehicle();
        $inspection = $this->inspection($vehicle)->setIsDeleted(true);
        $response = $this->controller(['router' => $this->router('/inspection')])
            ->show($inspection, $this->vehicleManager(true), new User(), $vehicle, $this->createMock(DocumentRepository::class));
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->new(new Request(), $this->createMock(EntityManagerInterface::class), $this->vehicleManager(false), new User(), $vehicle);
        self::assertSame(303, $response->getStatusCode());
    }

    /** @param array<string, mixed> $services */
    private function controller(array $services = [], bool $admin = true): VehicleInspectionController
    {
        return $this->wireController(new VehicleInspectionController(), $services, $admin);
    }

    private function vehicleManager(bool $authorized): VehicleManager
    {
        $manager = $this->createMock(VehicleManager::class);
        $manager->method('isAuthorized')->willReturn($authorized);

        return $manager;
    }

    private function vehicle(): Vehicle
    {
        return $this->setEntityId((new Vehicle())->setName('van')->setRegistration('ab-123-cd')->setLastMileage(100), 1);
    }

    private function inspection(Vehicle $vehicle): VehicleInspection
    {
        return $this->setEntityId((new VehicleInspection())->setVehicle($vehicle)->setInspectionDate(new \DateTimeImmutable('2024-01-02')), 2);
    }

    /** @return array{currentMileage:int, submittedMileage:int, fieldError:string} */
    private function warning(): array
    {
        return ['currentMileage' => 100, 'submittedMileage' => 90, 'fieldError' => 'Mileage too low'];
    }

    private function submittedValidFormWithMileageError(bool $expectError = true): FormInterface
    {
        $mileage = $this->createMock(FormInterface::class);
        $mileage->expects($expectError ? self::once() : self::never())->method('addError');
        $form = $this->submittedValidForm();
        $form->method('get')->with('mileage')->willReturn($mileage);

        return $form;
    }

    private function uploadedFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'inspection-document-');
        self::assertIsString($path);
        file_put_contents($path, 'document');

        return new UploadedFile($path, 'inspection.pdf', 'application/pdf', null, true);
    }

    private function documentsDirectory(): string
    {
        $directory = sys_get_temp_dir().'/gmao-inspection-documents';
        if (!is_dir($directory)) {
            mkdir($directory);
        }

        return $directory;
    }
}
