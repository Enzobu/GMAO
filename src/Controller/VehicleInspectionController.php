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
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/vehicle')]
final class VehicleInspectionController extends AbstractController
{
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

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();

            if ($uploadedFile !== null) {
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $extension = $uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension() ?: 'bin';

                $mimeType = $uploadedFile->getMimeType();
                $size = $uploadedFile->getSize();

                $storedFilename = sprintf('%s-%s.%s', $safeFilename, uniqid(), $extension);

                try {
                    $uploadedFile->move(
                        $this->getParameter('documents_directory'),
                        $storedFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Le fichier n’a pas pu être envoyé.');

                    return $this->render('document/new.html.twig', [
                        'document' => $document,
                        'form' => $form,
                        'entity' => $vehicleInspection,
                        'subtitle' => 'Assurance : ' . ucfirst($vehicleInspection->getInspectionDate()->format('d-m-Y')),
                    ]);
                }

                $document
                    ->setvehicleInspection($vehicleInspection)
                    ->setOriginalFilename($uploadedFile->getClientOriginalName())
                    ->setStoredFilename($storedFilename)
                    ->setMimeType($mimeType)
                    ->setSize($size)
                    ->setExtension($extension)
                ;

                if (!$document->getName()) {
                    $document->setName($originalFilename);
                }

                $entityManager->persist($document);
                $entityManager->flush();

                $this->addFlash('success', 'Le document a bien été ajouté.');

                return $this->redirectToRoute('app_vehicle_inspection_show', [
                    'vehicleId' => $vehicle->getId(),
                    'id' => $vehicleInspection->getId(),
                ], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('document/new.html.twig', [
            'document' => $document,
            'form' => $form,
            'entity' => $vehicleInspection,
            'subtitle' => 'Contrôle technique du : ' . ucfirst($vehicleInspection->getInspectionDate()->format('d-m-Y')),
        ]);
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
            $entityManager->flush();

            $name = $document->getName();
            $description = $document->getDescription();

            if (($oldName != $name) || ($oldDescription != $description)) {
                $this->addFlash('success', 'Le document a bien été modifié.');
            } else {
                $this->addFlash('warning', 'Le document ne comporte aucune modification.');
            }

            return $this->redirectToRoute('app_vehicle_inspection_show', [
                'vehicleId' => $vehicle->getId(),
                'id' => $vehicleInspection->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('document/edit.html.twig', [
            'document' => $document,
            'form' => $form,
            'entity' => $vehicleInspection,
            'subtitle' => 'Contrôle technique du : ' . ucfirst($vehicleInspection->getInspectionDate()->format('d-m-Y')),
        ]);
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

        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->getPayload()->getString('_token'))) {
            $documentManager->softDelete($document);
            
            $this->addFlash('success', 'Document supprimé avec succès.');
        }

        return $this->redirectToRoute('app_vehicle_inspection_show', [
            'vehicleId' => $vehicle->getId(),
            'id' => $vehicleInspection->getId(),
        ], Response::HTTP_SEE_OTHER);
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

    private function checkAthorization(
        VehicleManager $vehicleManager,
        User $currentUser,
        Vehicle $vehicle,
        VehicleInspection $vehicleInspection,
        ?Document $document = null,
        ?Array $params = [],
        bool $delete = false,
        bool $update = false,
        bool $new = false,
    ): ?Response {
        # -------------------- Authization --------------------
        if (!$vehicleManager->isAuthorized($currentUser, $vehicle)) {
            $update ? 
            $this->addFlash('danger', 'Vous ne pouvez pas modifier la ressource demandée. ressoPour plus d\'informations, contactez un administrateururce demandée.') : 
            $this->addFlash('warning', 'Vous avez un accès en lecture seule à la ressource demandée. ressoPour plus d\'informations, contactez un administrateururce demandée.');
            if ($update) {
                return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        if ($vehicle->isDeleted()) {
            $this->addFlash('danger', 'Le véhicule a été supprimé. ressoPour plus d\'informations, contactez un administrateururce demandée.');
            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }
        if ($vehicleInspection->isDeleted()) {
            $this->addFlash('warning', 'Le contrôle technique demandé a été supprimé. ressoPour plus d\'informations, contactez un administrateururce demandée.');
            return $this->redirectToRoute('app_vehicle_inspection_index', $params, Response::HTTP_SEE_OTHER);
        }
        if ($new) {
            if (!$vehicleManager->isAuthorized($currentUser, $vehicle)) {
                $this->addFlash('danger', 'Vous ne pouvez pas ajouter un contrôle technique pour ce vehicule. ressoPour plus d\'informations, contactez un administrateururce demandée.');
                return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        if ($document) {
            if (!$vehicleManager->isAuthorized($currentUser, $vehicle)) {
                $this->addFlash('danger', 'Vous ne pouvez pas ajouter un document sur la ressource demandée. ressoPour plus d\'informations, contactez un administrateururce demandée.');
                return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
            }
            if ($document->isDeleted()) {
                $this->addFlash('danger', 'Le document a été supprimé. ressoPour plus d\'informations, contactez un administrateururce demandée.');
                return $this->redirectToRoute('app_vehicle_inspection_index', $params, Response::HTTP_SEE_OTHER);
            }
        }
        if ($delete) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('danger', 'Vous n\'avez pas les autorisations nécessaires pour supprimer un document. Veuillez contacter un administrateur');
                return $this->redirectToRoute('app_vehicle_show', ["id" => $params["vehicleId"]], Response::HTTP_SEE_OTHER);
            }
        }
        # -----------------------------------------------------
        return null;
    }
}
