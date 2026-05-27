<?php

namespace App\Controller;

use App\Entity\Maintenance;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Form\MaintenanceType;
use App\Repository\DocumentRepository;
use App\Repository\MaintenanceRepository;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/vehicle/{vehicleId}/maintenance')]
final class VehicleMaintenanceController extends AbstractController
{
    #[Route(name: 'app_vehicle_maintenance_index', methods: ['GET'])]
    public function index(
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        MaintenanceRepository $maintenanceRepository,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkVehicleAuthorization($vehicleManager, $currentUser, $vehicle);

        if ($response) {
            return $response;
        }

        return $this->render('vehicle_maintenance/index.html.twig', [
            'vehicle' => $vehicle,
            'maintenances' => $maintenanceRepository->findForVehicle($vehicle),
        ]);
    }

    #[Route('/new', name: 'app_vehicle_maintenance_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkVehicleAuthorization($vehicleManager, $currentUser, $vehicle, update: true);

        if ($response) {
            return $response;
        }

        $maintenance = (new Maintenance())->setVehicle($vehicle);
        $mileageWarning = null;

        $form = $this->createForm(MaintenanceType::class, $maintenance, ['vehicle_locked' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newMileage = $this->getMileageContribution($maintenance);
            $warning = $vehicleManager->buildEventMileageWarning(
                oldVehicle: null,
                oldMileage: null,
                newVehicle: $vehicle,
                newMileage: $newMileage,
            );

            if ($this->shouldStopForMileageWarning($request, $form, $warning, $mileageWarning)) {
                return $this->render('vehicle_maintenance/new.html.twig', [
                    'vehicle' => $vehicle,
                    'maintenance' => $maintenance,
                    'form' => $form,
                    'mileage_warning' => $mileageWarning,
                ]);
            }

            foreach ($maintenance->getMaintenanceParts() as $maintenancePart) {
                $maintenancePart->setMaintenance($maintenance);
            }

            $entityManager->persist($maintenance);
            $entityManager->flush();

            if ($vehicleManager->syncAfterEventMileageChange(null, null, $vehicle, $newMileage, null)) {
                $entityManager->flush();
            }

            $this->addFlash('success', 'L’entretien a bien été créé.');

            return $this->redirectToRoute('app_vehicle_maintenance_index', [
                'vehicleId' => $vehicle->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle_maintenance/new.html.twig', [
            'vehicle' => $vehicle,
            'maintenance' => $maintenance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_vehicle_maintenance_show', methods: ['GET'])]
    public function show(
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        Maintenance $maintenance,
        DocumentRepository $documentRepository,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkMaintenanceAuthorization($vehicleManager, $currentUser, $vehicle, $maintenance);

        if ($response) {
            return $response;
        }

        return $this->render('maintenance/show.html.twig', [
            'maintenance' => $maintenance,
            'maintenance_document' => $documentRepository->findByMaintenance(maintenance: $maintenance, deleted: false),
            'vehicle_context' => true,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_vehicle_maintenance_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        Maintenance $maintenance,
        EntityManagerInterface $entityManager,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkMaintenanceAuthorization($vehicleManager, $currentUser, $vehicle, $maintenance, update: true);

        if ($response) {
            return $response;
        }

        $oldVehicle = $maintenance->getVehicle();
        $oldMileage = $this->getMileageContribution($maintenance);
        $oldVehicleLastMileage = $oldVehicle?->getLastMileage();
        $mileageWarning = null;

        $form = $this->createForm(MaintenanceType::class, $maintenance, ['vehicle_locked' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $maintenance->setVehicle($vehicle);
            $newMileage = $this->getMileageContribution($maintenance);
            $warning = $vehicleManager->buildEventMileageWarning(
                oldVehicle: $oldVehicle,
                oldMileage: $oldMileage,
                newVehicle: $vehicle,
                newMileage: $newMileage,
            );

            if ($this->shouldStopForMileageWarning($request, $form, $warning, $mileageWarning)) {
                return $this->render('vehicle_maintenance/edit.html.twig', [
                    'vehicle' => $vehicle,
                    'maintenance' => $maintenance,
                    'form' => $form,
                    'mileage_warning' => $mileageWarning,
                ]);
            }

            foreach ($maintenance->getMaintenanceParts() as $maintenancePart) {
                $maintenancePart->setMaintenance($maintenance);
            }

            $entityManager->flush();

            if ($vehicleManager->syncAfterEventMileageChange($oldVehicle, $oldMileage, $vehicle, $newMileage, $oldVehicleLastMileage)) {
                $entityManager->flush();
            }

            $this->addFlash('success', 'L’entretien a bien été modifié.');

            return $request->query->get('show') == 'true'
                ? $this->redirectToRoute('app_vehicle_maintenance_show', [
                    'vehicleId' => $vehicle->getId(),
                    'id' => $maintenance->getId(),
                ], Response::HTTP_SEE_OTHER)
                : $this->redirectToRoute('app_vehicle_maintenance_index', [
                    'vehicleId' => $vehicle->getId(),
                ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle_maintenance/edit.html.twig', [
            'vehicle' => $vehicle,
            'maintenance' => $maintenance,
            'form' => $form,
        ]);
    }

    private function getMileageContribution(Maintenance $maintenance): ?int
    {
        return $maintenance->getFinishedAt() !== null ? $maintenance->getMileage() : null;
    }

    private function shouldStopForMileageWarning(
        Request $request,
        FormInterface $form,
        ?array $warning,
        ?array &$mileageWarning,
    ): bool {
        $mileageWarning = null;

        if ($warning === null) {
            return false;
        }

        if ($this->isGranted('ROLE_ADMIN') && $request->request->get(VehicleManager::FORCE_MILEAGE_FIELD) === '1') {
            return false;
        }

        $form->get('mileage')->addError(new FormError($warning['fieldError']));

        if ($this->isGranted('ROLE_ADMIN')) {
            $mileageWarning = $warning;
        }

        return true;
    }

    private function checkVehicleAuthorization(
        VehicleManager $vehicleManager,
        User $currentUser,
        Vehicle $vehicle,
        bool $update = false,
    ): ?Response {
        if ($vehicle->isDeleted()) {
            $this->addFlash('danger', 'Le véhicule a été supprimé. Pour plus d’informations, contactez un administrateur.');

            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        if (!$vehicleManager->isAuthorized($currentUser, $vehicle)) {
            $this->addFlash(
                $update ? 'danger' : 'warning',
                $update
                    ? 'Vous ne pouvez pas modifier les entretiens de ce véhicule. Pour plus d’informations, contactez un administrateur.'
                    : 'Vous avez un accès en lecture seule aux entretiens de ce véhicule. Pour plus d’informations, contactez un administrateur.'
            );

            if ($update) {
                return $this->redirectToRoute('app_vehicle_maintenance_index', [
                    'vehicleId' => $vehicle->getId(),
                ], Response::HTTP_SEE_OTHER);
            }
        }

        return null;
    }

    private function checkMaintenanceAuthorization(
        VehicleManager $vehicleManager,
        User $currentUser,
        Vehicle $vehicle,
        Maintenance $maintenance,
        bool $update = false,
    ): ?Response {
        $response = $this->checkVehicleAuthorization($vehicleManager, $currentUser, $vehicle, $update);

        if ($response) {
            return $response;
        }

        if ($maintenance->isDeleted()) {
            $this->addFlash('danger', 'L’entretien a été supprimé. Pour plus d’informations, contactez un administrateur.');

            return $this->redirectToRoute('app_vehicle_maintenance_index', [
                'vehicleId' => $vehicle->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        if ($maintenance->getVehicle()?->getId() !== $vehicle->getId()) {
            throw $this->createNotFoundException('Entretien introuvable pour ce véhicule.');
        }

        return null;
    }
}
