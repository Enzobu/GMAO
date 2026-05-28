<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Form\DocumentType;
use App\Form\VehicleType;
use App\Repository\DocumentRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\VehicleInspectionRepository;
use App\Repository\VehicleInsuranceRepository;
use App\Repository\VehicleRepository;
use App\Service\DocumentManager;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/vehicle')]
final class VehicleController extends AbstractController
{
    use DocumentUploadTrait;
    use MileageWarningTrait;

    #[Route(name: 'app_vehicle_index', methods: ['GET'])]
    public function index(
        VehicleRepository $vehicleRepository,
    ): Response {
        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicleRepository->findAllNotDeleted(),
        ]);
    }

    #[Route('/new', name: 'app_vehicle_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $vehicle = (new Vehicle())->setUser($currentUser);

        $form = $this->createForm(VehicleType::class, $vehicle, ['edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $vehicle->setUser($currentUser);
            }
            $entityManager->persist($vehicle);
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle/new.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_vehicle_show', methods: ['GET'])]
    public function show(
        Vehicle $vehicle,
        VehicleManager $vehicleManager,
        VehicleInsuranceRepository $vehicleInsuranceRepository,
        VehicleInspectionRepository $vehicleInspectionRepository,
        #[CurrentUser] User $currentUser,
        DocumentRepository $documentRepository,
        MaintenanceRepository $maintenanceRepository,
    ): Response
    {
        $response = $this->checkAuthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            params: ["id" => $vehicle->getId()],
        );

        if ($response) {
            return $response;
        }

        $insurance = $vehicleInsuranceRepository->findBy([
            "vehicle" => $vehicle,
            "isDeleted" => false,
        ], ['startDate' => 'DESC']);

        $inspection = $vehicleInspectionRepository->findBy([        
            "vehicle" => $vehicle,
            "isDeleted" => false,
        ], ['inspectionDate' => 'DESC']);

        return $this->render('vehicle/show.html.twig', [
            'vehicle' => $vehicle,
            'insurance' => $insurance[0] ?? null,
            'inspection' => $inspection[0] ?? null,
            'latest_maintenance' => $maintenanceRepository->findLatestPerformedByVehicle($vehicle),
            'vehicle_document' => $documentRepository->findByVehicle(vehicle: $vehicle, deleted: false),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_vehicle_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Vehicle $vehicle, 
        EntityManagerInterface $entityManager,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAuthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            params: ["id" => $vehicle->getId()],
            update: true,
        );

        if ($response) {
            return $response;
        }

        $oldMileage = $vehicle->getLastMileage();
        $mileageWarning = null;

        $form = $this->createForm(VehicleType::class, $vehicle, ['edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $warning = $vehicleManager->buildVehicleMileageWarning($oldMileage, $vehicle->getLastMileage());

            if ($this->shouldStopForMileageWarning($request, $form, $warning, $mileageWarning, 'lastMileage')) {
                return $this->render('vehicle/edit.html.twig', [
                    'vehicle' => $vehicle,
                    'form' => $form,
                    'mileage_warning' => $mileageWarning,
                ]);
            }

            $entityManager->flush();
            
            $this->addFlash('success', 'Modifications enregistrées.');

            return $request->query->get('show') == 'true' ?
                $this->redirectToRoute('app_vehicle_show', ["id" => $vehicle->getId()], Response::HTTP_SEE_OTHER) :
                $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle/edit.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_vehicle_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Vehicle $vehicle, 
        EntityManagerInterface $entityManager,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAuthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            delete: true,
            params: ["id" => $vehicle->getId()],
        );

        if ($response) {
            return $response;
        }

        if ($this->isCsrfTokenValid('delete'.$vehicle->getId(), $request->getPayload()->getString('_token'))) {
            $vehicle->setIsDeleted(true);
            $entityManager->flush();

            $this->addFlash('success', $vehicle->getName() . ' a bien été supprimé.');
        }

        return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/document/new', name: 'app_vehicle_document_new', methods: ['GET', 'POST'])]
    public function newDocument(
        Request $request,
        EntityManagerInterface $entityManager,
        Vehicle $vehicle,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $document = new Document();

        $response = $this->checkAuthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            params: ["id" => $vehicle->getId()],
            document: $document,
        );

        if ($response) {
            return $response;
        }

        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        $response = $this->persistUploadedDocumentFromForm(
            $document,
            $form,
            $entityManager,
            static fn (Document $document) => $document->setVehicle($vehicle),
            fn () => $this->renderDocumentForm('document/new.html.twig', $document, $form, $vehicle, 'Véhicule : ' . ucfirst($vehicle->getName()) . ' ・ ' . strtoupper($vehicle->getRegistration())),
            fn () => $this->redirectToRoute('app_vehicle_show', [
                'id' => $vehicle->getId(),
            ], Response::HTTP_SEE_OTHER),
        );

        if ($response) {
            return $response;
        }

        return $this->renderDocumentForm('document/new.html.twig', $document, $form->createView(), $vehicle, 'Véhicule : ' . ucfirst($vehicle->getName()) . ' ・ ' . strtoupper($vehicle->getRegistration()));
    }

    #[Route('/{id}/document/{documentId}/edit', name: 'app_vehicle_document_edit', methods: ['GET', 'POST'])]
    public function editDocument(
        Request $request, 
        EntityManagerInterface $entityManager,
        Vehicle $vehicle,
        #[MapEntity(mapping: ['documentId' => 'publicId'])] Document $document,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAuthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            params: ["id" => $vehicle->getId()],
            update: true,
            document: $document,
        );

        if ($response) {
            return $response;
        }

        $oldName = $document->getName();
        $oldDescription = $document->getDescription();

        $form = $this->createForm(DocumentType::class, $document, ['edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->flushDocumentUpdate($entityManager, $document, $oldName, $oldDescription);

            return $this->redirectToRoute('app_vehicle_show', [
                'id' => $vehicle->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->renderDocumentForm('document/edit.html.twig', $document, $form, $vehicle, 'Véhicule : ' . ucfirst($vehicle->getName()) . ' ・ ' . strtoupper($vehicle->getRegistration()));
    }

    #[Route('{id}/document/{documentId}', name: 'app_vehicle_document_delete', methods: ['POST'])]
    public function deleteDocument(
        Request $request,
        Vehicle $vehicle,
        #[MapEntity(mapping: ['documentId' => 'publicId'])] Document $document,
        DocumentManager $documentManager,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAuthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            delete: true,
            params: ["id" => $vehicle->getId()],
            document: $document,
        );

        if ($response) {
            return $response;
        }

        $this->softDeleteDocumentWhenCsrfIsValid($request, $documentManager, $document);

        return $this->redirectToRoute('app_vehicle_show', ["id" => $vehicle->getId()], Response::HTTP_SEE_OTHER);
    }

    private function checkAuthorization(
        VehicleManager $vehicleManager,
        User $currentUser,
        Vehicle $vehicle,
        ?Document $document = null,
        ?Array $params = [],
        bool $delete = false,
        bool $update = false,
    ): ?Response {
        # -------------------- Authization --------------------
        if (!$vehicleManager->isAuthorized($currentUser, $vehicle)) {
            if ($update) {
                $this->addFlash('danger', 'Vous ne pouvez pas modifier la ressource demandée. Pour plus d\'informations, contactez un administrateur.');
            } else {
                $this->addFlash('warning', 'Vous avez un accès en lecture seule à la ressource demandée. Pour plus d\'informations, contactez un administrateur.');
            }
            if ($update) {
                return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        if ($vehicle->isDeleted()) {
            $this->addFlash('danger', 'Le véhicule a été supprimé. Pour plus d\'informations, contactez un administrateur.');
            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }
        if ($document) {
            if (!$vehicleManager->isAuthorized($currentUser, $vehicle)) {
                $this->addFlash('danger', 'Vous ne pouvez pas ajouter un document sur la ressource demandée. Pour plus d\'informations, contactez un administrateur.');
                return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
            }
            if ($document->isDeleted()) {
                return $this->redirectIfDocumentIsDeleted($document, 'app_vehicle_index', [], 'Le document a été supprimé. Pour plus d\'informations, contactez un administrateur.');
            }
            if ($delete) {
                $response = $this->redirectUnlessAdmin('app_vehicle_show', $params ?? [], 'Vous n\'avez pas les autorisations nécessaires pour supprimer un document. Veuillez contacter un administrateur');

                if ($response) {
                    return $response;
                }
            }
        }
        if ($delete) {
            $response = $this->redirectUnlessAdmin('app_vehicle_show', $params ?? [], 'Vous n\'avez pas les autorisations nécessaires pour supprimer un véhicule. Veuillez contacter un administrateur');

            if ($response) {
                return $response;
            }
        }
        # -----------------------------------------------------
        return null;
    }
}
