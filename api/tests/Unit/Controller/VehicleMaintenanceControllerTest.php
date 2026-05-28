<?php

namespace App\Tests\Unit\Controller;

use App\Controller\VehicleMaintenanceController;
use App\Entity\Maintenance;
use App\Entity\MaintenancePart;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Repository\DocumentRepository;
use App\Repository\MaintenanceRepository;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class VehicleMaintenanceControllerTest extends ControllerUnitTestCase
{
    public function testIndexRendersVehicleMaintenances(): void
    {
        $vehicle = $this->vehicle();
        $maintenances = [new Maintenance()];
        $repository = $this->createMock(MaintenanceRepository::class);
        $repository->expects(self::once())->method('findForVehicle')->with($vehicle)->willReturn($maintenances);

        $response = $this->controller([
            'twig' => $this->twigExpecting('vehicle_maintenance/index.html.twig', static fn (array $p): bool => $p['vehicle'] === $vehicle && $p['maintenances'] === $maintenances),
        ])->index($vehicle, $repository, $this->vehicleManager(true), new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testIndexAllowsReadonlyUnauthorizedUser(): void
    {
        $vehicle = $this->vehicle();
        $repository = $this->createMock(MaintenanceRepository::class);
        $repository->method('findForVehicle')->willReturn([]);

        $response = $this->controller([
            'twig' => $this->twigExpecting('vehicle_maintenance/index.html.twig', static fn (array $p): bool => $p['vehicle'] === $vehicle),
        ])->index($vehicle, $repository, $this->vehicleManager(false), new User());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testNewRendersInitialForm(): void
    {
        $vehicle = $this->vehicle();
        $form = $this->notSubmittedForm();

        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'twig' => $this->twigExpecting('vehicle_maintenance/new.html.twig', static fn (array $p): bool => $p['vehicle'] === $vehicle && $p['maintenance'] instanceof Maintenance && isset($p['form'])),
        ])->new(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $this->vehicleManager(true), new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testNewPersistsAndSyncsMileage(): void
    {
        $vehicle = $this->vehicle();
        $manager = $this->vehicleManager(true);
        $manager->method('buildEventMileageWarning')->willReturn(null);
        $manager->method('syncAfterEventMileageChange')->willReturn(true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Maintenance::class));
        $entityManager->expects(self::exactly(2))->method('flush');

        $response = $this->controller([
            'form.factory' => $this->maintenanceFormFactory($this->submittedValidForm(), addPart: true),
            'router' => $this->router('/vehicle/1/maintenance'),
        ])->new(new Request(), $entityManager, $vehicle, $manager, new User());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testNewStopsForMileageWarningForNonAdmin(): void
    {
        $vehicle = $this->vehicle();
        $form = $this->submittedValidFormWithMileageError();
        $manager = $this->vehicleManager(true);
        $manager->method('buildEventMileageWarning')->willReturn($this->warning());

        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'twig' => $this->twigExpecting('vehicle_maintenance/new.html.twig', static fn (array $p): bool => isset($p['mileage_warning']) === false),
        ], admin: false)->new(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $manager, new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testNewAdminCanForceMileageWarning(): void
    {
        $vehicle = $this->vehicle();
        $manager = $this->vehicleManager(true);
        $manager->method('buildEventMileageWarning')->willReturn($this->warning());
        $manager->method('syncAfterEventMileageChange')->willReturn(false);

        $request = new Request([], [VehicleManager::FORCE_MILEAGE_FIELD => '1']);

        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidFormWithMileageError(false)),
            'router' => $this->router('/vehicle/1/maintenance'),
        ])->new($request, $this->createMock(EntityManagerInterface::class), $vehicle, $manager, new User());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testShowRendersMaintenanceDocuments(): void
    {
        $vehicle = $this->vehicle();
        $maintenance = $this->maintenance($vehicle);
        $documents = [new \App\Entity\Document()];
        $repository = $this->createMock(DocumentRepository::class);
        $repository->method('findByMaintenance')->with($maintenance, false)->willReturn($documents);

        $response = $this->controller([
            'twig' => $this->twigExpecting('maintenance/show.html.twig', static fn (array $p): bool => $p['maintenance'] === $maintenance && $p['maintenance_document'] === $documents && $p['vehicle_context'] === true),
        ])->show($vehicle, $maintenance, $repository, $this->vehicleManager(true), new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditRendersInitialForm(): void
    {
        $vehicle = $this->vehicle();
        $maintenance = $this->maintenance($vehicle);
        $form = $this->notSubmittedForm();

        $response = $this->controller([
            'form.factory' => $this->formFactory($form),
            'twig' => $this->twigExpecting('vehicle_maintenance/edit.html.twig', static fn (array $p): bool => $p['vehicle'] === $vehicle && $p['maintenance'] === $maintenance && isset($p['form'])),
        ])->edit(new Request(), $vehicle, $maintenance, $this->createMock(EntityManagerInterface::class), $this->vehicleManager(true), new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditFlushesAndRedirectsToShow(): void
    {
        $vehicle = $this->vehicle();
        $maintenance = $this->maintenance($vehicle)->setFinishedAt(new \DateTimeImmutable())->setMileage(120);
        $maintenance->addMaintenancePart(new MaintenancePart());
        $manager = $this->vehicleManager(true);
        $manager->method('buildEventMileageWarning')->willReturn(null);
        $manager->method('syncAfterEventMileageChange')->willReturn(true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))->method('flush');

        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidForm()),
            'router' => $this->router('/vehicle/1/maintenance/2'),
        ])->edit(new Request(['show' => 'true']), $vehicle, $maintenance, $entityManager, $manager, new User());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testEditStopsForMileageWarning(): void
    {
        $vehicle = $this->vehicle();
        $maintenance = $this->maintenance($vehicle)->setFinishedAt(new \DateTimeImmutable())->setMileage(90);
        $manager = $this->vehicleManager(true);
        $manager->method('buildEventMileageWarning')->willReturn($this->warning());

        $response = $this->controller([
            'form.factory' => $this->formFactory($this->submittedValidFormWithMileageError()),
            'twig' => $this->twigExpecting('vehicle_maintenance/edit.html.twig', static fn (array $p): bool => $p['vehicle'] === $vehicle && $p['maintenance'] === $maintenance && $p['mileage_warning']['fieldError'] === 'Mileage too low'),
        ])->edit(new Request(), $vehicle, $maintenance, $this->createMock(EntityManagerInterface::class), $manager, new User());

        self::assertSame('rendered', $response->getContent());
    }

    public function testEditThrowsNotFoundForMaintenanceFromOtherVehicle(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->controller()->edit(
            new Request(),
            $this->vehicle(1),
            $this->maintenance($this->vehicle(2)),
            $this->createMock(EntityManagerInterface::class),
            $this->vehicleManager(true),
            new User(),
        );
    }

    public function testDeletedVehicleRedirectsBeforeAction(): void
    {
        $vehicle = $this->vehicle()->setIsDeleted(true);

        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->new(new Request(), $this->createMock(EntityManagerInterface::class), $vehicle, $this->vehicleManager(true), new User());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeletedVehicleRedirectsBeforeIndexAndShow(): void
    {
        $vehicle = $this->vehicle()->setIsDeleted(true);

        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->index($vehicle, $this->createMock(MaintenanceRepository::class), $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());

        $response = $this->controller(['router' => $this->router('/vehicles')])
            ->show($vehicle, $this->maintenance($vehicle), $this->createMock(DocumentRepository::class), $this->vehicleManager(true), new User());
        self::assertSame(303, $response->getStatusCode());
    }

    public function testUnauthorizedUpdateRedirectsBeforeAction(): void
    {
        $response = $this->controller(['router' => $this->router('/vehicle/1/maintenance')])
            ->new(new Request(), $this->createMock(EntityManagerInterface::class), $this->vehicle(), $this->vehicleManager(false), new User());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testUnauthorizedEditRedirectsBeforeAction(): void
    {
        $vehicle = $this->vehicle();

        $response = $this->controller(['router' => $this->router('/vehicle/1/maintenance')])
            ->edit(new Request(), $vehicle, $this->maintenance($vehicle), $this->createMock(EntityManagerInterface::class), $this->vehicleManager(false), new User());

        self::assertSame(303, $response->getStatusCode());
    }

    public function testDeletedMaintenanceRedirectsBeforeShow(): void
    {
        $vehicle = $this->vehicle();
        $maintenance = $this->maintenance($vehicle)->setIsDeleted(true);

        $response = $this->controller(['router' => $this->router('/vehicle/1/maintenance')])
            ->show($vehicle, $maintenance, $this->createMock(DocumentRepository::class), $this->vehicleManager(true), new User());

        self::assertSame(303, $response->getStatusCode());
    }

    /** @param array<string, mixed> $services */
    private function controller(array $services = [], bool $admin = true): VehicleMaintenanceController
    {
        return $this->wireController(new VehicleMaintenanceController(), $services, $admin);
    }

    private function vehicleManager(bool $authorized): VehicleManager
    {
        $manager = $this->createMock(VehicleManager::class);
        $manager->method('isAuthorized')->willReturn($authorized);

        return $manager;
    }

    private function vehicle(int $id = 1): Vehicle
    {
        return $this->setEntityId((new Vehicle())->setName('van')->setRegistration('ab-123-cd')->setLastMileage(100), $id);
    }

    private function maintenance(Vehicle $vehicle): Maintenance
    {
        return $this->setEntityId((new Maintenance())->setVehicle($vehicle), 2);
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

    private function maintenanceFormFactory(FormInterface $form, bool $addPart = false): FormFactoryInterface
    {
        $factory = $this->createMock(FormFactoryInterface::class);
        $factory->method('create')->willReturnCallback(static function (string $type, Maintenance $maintenance) use ($form, $addPart): FormInterface {
            if ($addPart) {
                $maintenance->addMaintenancePart(new MaintenancePart());
            }

            return $form;
        });

        return $factory;
    }
}
