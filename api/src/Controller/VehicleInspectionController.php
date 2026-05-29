<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Form\DocumentType;
use App\Form\VehicleInspectionType;
use App\Repository\DocumentRepository;
use App\Repository\VehicleInspectionRepository;
use App\Service\DocumentManager;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/vehicle')]
final class VehicleInspectionController extends AbstractController
{
    use DocumentUploadTrait;
    use MileageWarningTrait;
    use VehicleEventAuthorizationTrait;

    #[Route('/{vehicleId}/inspection', name: 'app_vehicle_inspection_index', methods: ['GET'])]
    public function index(
        VehicleInspectionRepository $vehicleInspectionRepository,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInspection: new VehicleInspection(),
        );

        if ($response) {
            return $response;
        }

        $inspections = $vehicleInspectionRepository->findByVehicle([
            "vehicle" => $vehicle,
        ], ['inspectionDate' => 'DESC'], deleted: false);

        return $this->render('vehicle_inspection/index.html.twig', [
            'vehicle_inspections' => $inspections,
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/{vehicleId}/inspection/new', name: 'app_vehicle_inspection_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $vehicleInspection = new VehicleInspection();
        $mileageWarning = null;
        
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInspection: $vehicleInspection,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInspection->getId()],
            new: true,
        );

        if ($response) {
            return $response;
        }

        $form = $this->createForm(VehicleInspectionType::class, $vehicleInspection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $vehicleInspection->setVehicle($vehicle);
            $newMileage = $vehicleInspection->getMileage();
            $warning = $vehicleManager->buildEventMileageWarning(
                oldVehicle: null,
                oldMileage: null,
                newVehicle: $vehicle,
                newMileage: $newMileage,
            );

            if ($this->shouldStopForMileageWarning($request, $form, $warning, $mileageWarning)) {
                return $this->render('vehicle_inspection/new.html.twig', [
                    'vehicle_inspection' => $vehicleInspection,
                    'form' => $form,
                    'vehicle' => $vehicle,
                    'mileage_warning' => $mileageWarning,
                ]);
            }

            $entityManager->persist($vehicleInspection);
            $entityManager->flush();

            if ($vehicleManager->syncAfterEventMileageChange(null, null, $vehicle, $newMileage, null)) {
                $entityManager->flush();
            }

            return $this->redirectToRoute('app_vehicle_inspection_index', [
                'vehicleId' => $vehicle->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle_inspection/new.html.twig', [
            'vehicle_inspection' => $vehicleInspection,
            'form' => $form,
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/{vehicleId}/inspection/{id}', name: 'app_vehicle_inspection_show', methods: ['GET'])]
    public function show(
        VehicleInspection $vehicleInspection,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        DocumentRepository $documentRepository,
    ): Response {
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInspection: $vehicleInspection,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInspection->getId()],
        );

        if ($response) {
            return $response;
        }

        return $this->render('vehicle_inspection/show.html.twig', [
            'vehicle_inspection' => $vehicleInspection,
            'vehicle' => $vehicle,
            'vehicle_inspection_document' => $documentRepository->findByVehicleInspection(vehicleInspection: $vehicleInspection, deleted: false),
        ]);
    }

    #[Route('/{vehicleId}/inspection/{id}/edit', name: 'app_vehicle_inspection_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        VehicleInspection $vehicleInspection,
        VehicleManager $vehicleManager,
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $currentUser,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInspection: $vehicleInspection,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInspection->getId()],
            update: true,
        );

        if ($response) {
            return $response;
        }

        $oldVehicle = $vehicleInspection->getVehicle();
        $oldMileage = $vehicleInspection->getMileage();
        $oldVehicleLastMileage = $oldVehicle?->getLastMileage();
        $mileageWarning = null;

        $form = $this->createForm(VehicleInspectionType::class, $vehicleInspection, ['edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newMileage = $vehicleInspection->getMileage();
            $warning = $vehicleManager->buildEventMileageWarning(
                oldVehicle: $oldVehicle,
                oldMileage: $oldMileage,
                newVehicle: $vehicleInspection->getVehicle(),
                newMileage: $newMileage,
            );

            if ($this->shouldStopForMileageWarning($request, $form, $warning, $mileageWarning)) {
                return $this->render('vehicle_inspection/edit.html.twig', [
                    'vehicle_inspection' => $vehicleInspection,
                    'form' => $form,
                    'vehicle' => $vehicle,
                    'mileage_warning' => $mileageWarning,
                ]);
            }

            $entityManager->flush();

            if ($vehicleManager->syncAfterEventMileageChange($oldVehicle, $oldMileage, $vehicleInspection->getVehicle(), $newMileage, $oldVehicleLastMileage)) {
                $entityManager->flush();
            }

            $this->addFlash('success', 'Modifications enregistrées.');

            return $request->query->get('show') == 'true' ?
                $this->redirectToRoute('app_vehicle_inspection_show', [
                    "id" => $vehicleInspection->getId(),
                    "vehicleId" => $vehicle->getId(),
                ], Response::HTTP_SEE_OTHER) :
                $this->redirectToRoute('app_vehicle_inspection_index', [
                    "vehicleId" => $vehicle->getId(),
                ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle_inspection/edit.html.twig', [
            'vehicle_inspection' => $vehicleInspection,
            'form' => $form,
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/{vehicleId}/inspection/{id}', name: 'app_vehicle_inspection_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        VehicleInspection $vehicleInspection,
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInspection: $vehicleInspection,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInspection->getId()],
            delete: true,
        );

        if ($response) {
            return $response;
        }

        $oldVehicle = $vehicleInspection->getVehicle();
        $oldMileage = $vehicleInspection->getMileage();
        $oldVehicleLastMileage = $oldVehicle?->getLastMileage();

        if ($this->isCsrfTokenValid('delete' . $vehicleInspection->getId(), $request->getPayload()->getString('_token'))) {
            $vehicleInspection->setIsDeleted(true);
            $entityManager->flush();

            if ($vehicleManager->syncAfterEventMileageChange($oldVehicle, $oldMileage, null, null, $oldVehicleLastMileage)) {
                $entityManager->flush();
            }

            $this->addFlash('success', 'Contrôle technique supprimé avec succès.');
        }

        return $this->redirectToRoute('app_vehicle_inspection_index', [
            'vehicleId' => $vehicle->getId(),
        ], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{vehicleId}/inspection/{id}/document/new', name: 'app_vehicle_inspection_document_new', methods: ['GET', 'POST'])]
    public function newDocument(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        VehicleInspection $vehicleInspection,
        SluggerInterface $slugger,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $document = new Document();

        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInspection: $vehicleInspection,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInspection->getId()],
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
            static fn (Document $document) => $document->setvehicleInspection($vehicleInspection),
            fn () => $this->renderDocumentForm('document/new.html.twig', $document, $form, $vehicleInspection, 'Assurance : ' . ucfirst($vehicleInspection->getInspectionDate()->format('d-m-Y'))),
            fn () => $this->redirectToRoute('app_vehicle_inspection_show', [
                'vehicleId' => $vehicle->getId(),
                'id' => $vehicleInspection->getId(),
            ], Response::HTTP_SEE_OTHER),
            $slugger,
        );

        if ($response) {
            return $response;
        }

        return $this->renderDocumentForm('document/new.html.twig', $document, $form, $vehicleInspection, 'Contrôle technique du : ' . ucfirst($vehicleInspection->getInspectionDate()->format('d-m-Y')));
    }

    #[Route('/{vehicleId}/inspection/{id}/document/{documentId}/edit', name: 'app_vehicle_inspection_document_edit', methods: ['GET', 'POST'])]
    public function editDocument(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        VehicleInspection $vehicleInspection,
        #[MapEntity(mapping: ['documentId' => 'publicId'])] Document $document,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInspection: $vehicleInspection,
            document: $document,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInspection->getId()],
            update: true,
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

            return $this->redirectToRoute('app_vehicle_inspection_show', [
                'vehicleId' => $vehicle->getId(),
                'id' => $vehicleInspection->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->renderDocumentForm('document/edit.html.twig', $document, $form, $vehicleInspection, 'Contrôle technique du : ' . ucfirst($vehicleInspection->getInspectionDate()->format('d-m-Y')));
    }

    #[Route('/{vehicleId}/inspection/{id}/document/{documentId}', name: 'app_vehicle_inspection_document_delete', methods: ['POST'])]
    public function deleteDocument(
        Request $request,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        VehicleInspection $vehicleInspection,
        #[MapEntity(mapping: ['documentId' => 'publicId'])] Document $document,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
        DocumentManager $documentManager,
    ): Response {
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInspection: $vehicleInspection,
            document: $document,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInspection->getId()],
            delete: true,
        );

        if ($response) {
            return $response;
        }

        $this->softDeleteDocumentWhenCsrfIsValid($request, $documentManager, $document);

        return $this->redirectToRoute('app_vehicle_inspection_show', [
            'vehicleId' => $vehicle->getId(),
            'id' => $vehicleInspection->getId(),
        ], Response::HTTP_SEE_OTHER);
    }

    private function checkAthorization(
        VehicleManager $vehicleManager,
        User $currentUser,
        Vehicle $vehicle,
        VehicleInspection $vehicleInspection,
        ?Document $document = null,
        ?array $params = [],
        bool $delete = false,
        bool $update = false,
        bool $new = false,
    ): ?Response {
        return $this->checkVehicleEventAuthorization(
            $vehicleManager,
            $currentUser,
            $vehicle,
            $vehicleInspection,
            'app_vehicle_inspection_index',
            'Le contrôle technique demandé a été supprimé. ressoPour plus d\'informations, contactez un administrateururce demandée.',
            $document,
            $params ?? [],
            $delete,
            $update,
            $new,
        );
    }
}
