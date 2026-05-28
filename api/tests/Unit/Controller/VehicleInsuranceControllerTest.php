<?php

namespace App\Tests\Unit\Controller;

use App\Controller\VehicleInsuranceController;
use App\Entity\Document;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Repository\DocumentRepository;
use App\Repository\VehicleInsuranceRepository;
use App\Service\DocumentManager;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class VehicleInsuranceControllerTest extends ControllerUnitTestCase
{
    public function testIndexRendersInsurances(): void
    {
        $vehicle = $this->vehicle();
        $insurances = [$this->insurance($vehicle)];
        $repository = $this->createMock(VehicleInsuranceRepository::class);
        $repository->method('findByVehicle')->willReturn($insurances);

        $response = $this->controller([
            'twig' => $this->twigExpecting('vehicle_insurance/index.html.twig', static fn (array $p): bool => $p['vehicle'] === $vehicle && $p['vehicle_insurances'] === $insurances),
        ])->index($repository, $vehicle, new User(), $this->vehicleManager(true));

        self::assertSame('rendered', $response->getContent());
    }

    public function testNewRendersAndPersists(): void
    {
        $vehicle = $this->vehicle();
        $form = $this->notSubmittedForm();
        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'twig' => $this->twigExpecting('vehicle_insurance/new.html.twig', static fn (array $p): bool => $p['vehicle'] === $vehicle && $p['vehicle_insurance'] instanceof VehicleInsurance && isset($p['form'])),
        ])->new(new Request(), $this->createMock(EntityManagerInterface::class), new User(), $this->vehicleManager(true), $vehicle);
        self::assertSame('rendered', $response->getContent());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(VehicleInsurance::class));
        $entityManager->expects(self::once())->method('flush');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidForm()),
            'router' => $this->router('/insurance'),
        ])->new(new Request(), $entityManager, new User(), $this->vehicleManager(true), $vehicle);
        self::assertSame(303, $response->getStatusCode());
    }

    public function testShowAndEdit(): void
    {
        $vehicle = $this->vehicle();
        $insurance = $this->insurance($vehicle);
        $documents = [new Document()];
        $documentRepository = $this->createMock(DocumentRepository::class);
        $documentRepository->method('findByVehicleInsurance')->willReturn($documents);

        $response = $this->controller([
            'twig' => $this->twigExpecting('vehicle_insurance/show.html.twig', static fn (array $p): bool => $p['vehicle_insurance'] === $insurance && $p['vehicle_insurance_document'] === $documents),
        ])->show($insurance, $this->vehicleManager(true), new User(), $vehicle, $documentRepository);
        self::assertSame('rendered', $response->getContent());

        $form = $this->notSubmittedForm();
        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'twig' => $this->twigExpecting('vehicle_insurance/edit.html.twig', static fn (array $p): bool => $p['vehicle_insurance'] === $insurance && isset($p['form'])),
        ])->edit(new Request(), $insurance, $this->vehicleManager(true), $this->createMock(EntityManagerInterface::class), new User(), $vehicle);
        self::assertSame('rendered', $response->getContent());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidForm()),
            'router' => $this->router('/insurance/show'),
        ])->edit(new Request(['show' => 'true']), $insurance, $this->vehicleManager(true), $entityManager, new User(), $vehicle);
        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeleteRequiresCsrfAndAdmin(): void
    {
        $vehicle = $this->vehicle();
        $insurance = $this->insurance($vehicle);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $response = $this->controller([
            'security.csrf.token_manager' => $this->csrf(true),
            'router' => $this->router('/insurance'),
        ])->delete(new Request([], ['_token' => 'ok']), $insurance, $entityManager, $vehicle, $this->vehicleManager(true), new User());

        self::assertTrue($insurance->isDeleted());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicle/1')], admin: false)
            ->delete(new Request(), $insurance, $this->createMock(EntityManagerInterface::class), $vehicle, $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());
    }

    public function testNewDocumentRendersUploadsAndEditDeleteDocument(): void
    {
        $vehicle = $this->vehicle();
        $insurance = $this->insurance($vehicle);
        $form = $this->documentForm(null, false);
        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'twig' => $this->twigExpecting('document/new.html.twig', static fn (array $p): bool => $p['entity'] === $insurance && isset($p['form'])),
        ])->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $insurance, new AsciiSlugger(), new User(), $this->vehicleManager(true));
        self::assertSame('rendered', $response->getContent());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Document::class));
        $entityManager->expects(self::once())->method('flush');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->documentForm($this->uploadedFile())),
            'parameter_bag' => $this->parameterBag($this->documentsDirectory()),
            'router' => $this->router('/insurance/show'),
        ])->newDocument(new Request(), $entityManager, $vehicle, $insurance, new AsciiSlugger(), new User(), $this->vehicleManager(true));
        self::assertSame(303, $response->getStatusCode());

        $document = (new Document())->setName('old')->setDescription('old');
        $response = $this->controller([
            'form.factory' => $this->formFactory($this->notSubmittedForm()),
            'twig' => $this->twigExpecting('document/edit.html.twig', static fn (array $p): bool => $p['document'] === $document && $p['entity'] === $insurance),
        ])->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $insurance, $document, $this->vehicleManager(true), new User());
        self::assertSame('rendered', $response->getContent());

        $manager = $this->createMock(DocumentManager::class);
        $manager->expects(self::once())->method('softDelete')->with($document);
        $response = $this->controller([
            'security.csrf.token_manager' => $this->csrf(true),
            'router' => $this->router('/insurance/show'),
        ])->deleteDocument(new Request([], ['_token' => 'ok']), $vehicle, $insurance, $document, $this->vehicleManager(true), new User(), $manager);
        self::assertSame(303, $response->getStatusCode());
    }

    public function testDocumentUploadFailureRendersForm(): void
    {
        $vehicle = $this->vehicle();
        $insurance = $this->insurance($vehicle);

        $response = $this->controller([
            'form.factory' => $this->formFactory($this->documentForm($this->uploadedFileThrowingMove())),
            'parameter_bag' => $this->parameterBag($this->documentsDirectory()),
            'twig' => $this->twigExpecting('document/new.html.twig', static fn (array $p): bool => $p['entity'] === $insurance && $p['document'] instanceof Document),
        ])->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $insurance, new AsciiSlugger(), new User(), $this->vehicleManager(true));

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditDocumentSubmittedBranches(): void
    {
        $vehicle = $this->vehicle();
        $insurance = $this->insurance($vehicle);
        $document = (new Document())->setName('old')->setDescription('old');
        $form = $this->submittedValidFormHandling(static function () use ($document): void {
            $document->setName('new');
        });

        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'router' => $this->router('/insurance/show'),
        ])->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $insurance, $document, $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidForm()),
            'router' => $this->router('/insurance/show'),
        ])->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $insurance, $document, $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());
    }

    public function testAuthorizationRedirectBranchesForEditAndDocuments(): void
    {
        $vehicle = $this->vehicle();
        $insurance = $this->insurance($vehicle);
        $document = new Document();

        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->edit(new Request(), $insurance, $this->vehicleManager(false), $this->createMock(EntityManagerInterface::class), new User(), $vehicle);
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->newDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $insurance, new AsciiSlugger(), new User(), $this->vehicleManager(false));
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $insurance, $document, $this->vehicleManager(false), new User());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/insurance')])
            ->editDocument(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $insurance, (new Document())->setIsDeleted(true), $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicle/1')], admin: false)
            ->deleteDocument(new Request(), $vehicle, $insurance, $document, $this->vehicleManager(true), new User(), $this->createMock(DocumentManager::class));
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/insurance')])
            ->deleteDocument(new Request(), $vehicle, $insurance, $document, $this->vehicleManager(false), new User(), $this->createMock(DocumentManager::class));
        self::assertSame(303, $response->getStatusCode());
    }

    public function testAuthorizationRedirectBranches(): void
    {
        $vehicle = $this->vehicle()->setIsDeleted(true);
        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->index($this->createMock(VehicleInsuranceRepository::class), $vehicle, new User(), $this->vehicleManager(true));
        self::assertSame(303, $response->getStatusCode());

        $vehicle = $this->vehicle();
        $insurance = $this->insurance($vehicle)->setIsDeleted(true);
        $response = $this->controller(['router' => $this->router('/insurance')])
            ->show($insurance, $this->vehicleManager(true), new User(), $vehicle, $this->createMock(DocumentRepository::class));
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->new(new Request(), $this->createMock(EntityManagerInterface::class), new User(), $this->vehicleManager(false), $vehicle);
        self::assertSame(303, $response->getStatusCode());
    }

    /** @param array<string, mixed> $services */
    private function controller(array $services = [], bool $admin = true): VehicleInsuranceController
    {
        return $this->wireController(new VehicleInsuranceController(), $services, $admin);
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

    private function insurance(Vehicle $vehicle): VehicleInsurance
    {
        return $this->setEntityId((new VehicleInsurance())->setVehicle($vehicle)->setProviderName('axa'), 2);
    }

    private function uploadedFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'insurance-document-');
        self::assertIsString($path);
        file_put_contents($path, 'document');

        return new UploadedFile($path, 'contract.pdf', 'application/pdf', null, true);
    }

    private function documentsDirectory(): string
    {
        $directory = sys_get_temp_dir().'/gmao-insurance-documents';
        if (!is_dir($directory)) {
            mkdir($directory);
        }

        return $directory;
    }
}
