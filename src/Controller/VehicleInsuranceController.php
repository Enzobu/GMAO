<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Form\DocumentType;
use App\Form\VehicleInsuranceType;
use App\Repository\DocumentRepository;
use App\Repository\VehicleInsuranceRepository;
use App\Service\DocumentManager;
use App\Service\VehicleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/vehicle')]
final class VehicleInsuranceController extends AbstractController
{
    #[Route('/{vehicleId}/insurance', name: 'app_vehicle_insurance_index', methods: ['GET'])]
    public function index(
        VehicleInsuranceRepository $vehicleInsuranceRepository,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        #[CurrentUser] User $currentUser,
        VehicleManager $vehicleManager,
    ): Response {
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInsurance: new VehicleInsurance(),
        );

        if ($response) {
            return $response;
        }

        $insurances = $vehicleInsuranceRepository->findByVehicle([
            "vehicle" => $vehicle,
        ], ['startDate' => 'DESC'], deleted: false);

        return $this->render('vehicle_insurance/index.html.twig', [
            'vehicle_insurances' => $insurances,
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/{vehicleId}/insurance/new', name: 'app_vehicle_insurance_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $currentUser,
        VehicleManager $vehicleManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $vehicleInsurance = new VehicleInsurance();
        
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInsurance: $vehicleInsurance,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInsurance->getId()],
            new: true,
        );

        if ($response) {
            return $response;
        }

        $form = $this->createForm(VehicleInsuranceType::class, $vehicleInsurance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $vehicleInsurance->setVehicle($vehicle);

            $entityManager->persist($vehicleInsurance);
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicle_insurance_index', ['vehicleId' => $vehicle->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle_insurance/new.html.twig', [
            'vehicle_insurance' => $vehicleInsurance,
            'form' => $form,
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/{vehicleId}/insurance/{id}', name: 'app_vehicle_insurance_show', methods: ['GET'])]
    public function show(
        VehicleInsurance $vehicleInsurance,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        DocumentRepository $documentRepository,
    ): Response {
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInsurance: $vehicleInsurance,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInsurance->getId()],
        );

        if ($response) {
            return $response;
        }

        return $this->render('vehicle_insurance/show.html.twig', [
            'vehicle_insurance' => $vehicleInsurance,
            'vehicle' => $vehicle,
            'vehicle_insurance_document' => $documentRepository->findByVehicleInsurance(vehicleInsurance: $vehicleInsurance, deleted: false),
        ]);
    }

    #[Route('/{vehicleId}/insurance/{id}/edit', name: 'app_vehicle_insurance_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        VehicleInsurance $vehicleInsurance,
        VehicleManager $vehicleManager,
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $currentUser,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInsurance: $vehicleInsurance,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInsurance->getId()],
            update: true,
        );

        if ($response) {
            return $response;
        }

        $form = $this->createForm(VehicleInsuranceType::class, $vehicleInsurance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Modifications enregistrées.');

            return $request->query->get('show') == 'true' ?
                $this->redirectToRoute('app_vehicle_insurance_show', [
                    "id" => $vehicleInsurance->getId(),
                    "vehicleId" => $vehicle->getId(),
                ], Response::HTTP_SEE_OTHER) :
                $this->redirectToRoute('app_vehicle_insurance_index', [
                    "vehicleId" => $vehicle->getId(),
                ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle_insurance/edit.html.twig', [
            'vehicle_insurance' => $vehicleInsurance,
            'form' => $form,
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/{vehicleId}/insurance/{id}', name: 'app_vehicle_insurance_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        VehicleInsurance $vehicleInsurance,
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInsurance: $vehicleInsurance,
            delete: true,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInsurance->getId()],
        );

        if ($response) {
            return $response;
        }

        if ($this->isCsrfTokenValid('delete'.$vehicleInsurance->getId(), $request->getPayload()->getString('_token'))) {
            $vehicleInsurance->setIsDeleted(true);
            $entityManager->flush();
            
            $this->addFlash('success', 'Assurance supprimée avec succès.');
        }


        return $this->redirectToRoute('app_vehicle_insurance_index', ['vehicleId' => $vehicle->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{vehicleId}/insurance/{id}/document/new', name: 'app_vehicle_insurance_document_new', methods: ['GET', 'POST'])]
    public function newDocument(
        Request $request, 
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        VehicleInsurance $vehicleInsurance,
        SluggerInterface $slugger,
        #[CurrentUser] User $currentUser,
        VehicleManager $vehicleManager,
    ): Response {
        $document = new Document();

        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager,
            currentUser: $currentUser,
            vehicle: $vehicle,
            vehicleInsurance: $vehicleInsurance,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInsurance->getId()],
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
                        'entity' => $vehicleInsurance,
                        'subtitle' => 'Assurance : ' . ucfirst($vehicleInsurance->getProviderName()),
                    ]);
                }

                $document
                    ->setVehicleInsurance($vehicleInsurance)
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

                return $this->redirectToRoute('app_vehicle_insurance_show', [
                    'vehicleId' => $vehicle->getId(),
                    'id' => $vehicleInsurance->getId(),
                ], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('document/new.html.twig', [
            'document' => $document,
            'form' => $form,
            'entity' => $vehicleInsurance,
            'subtitle' => 'Assurance : ' . ucfirst($vehicleInsurance->getProviderName()),
        ]);
    }

    #[Route('/{vehicleId}/insurance/{id}/document/{documentId}/edit', name: 'app_vehicle_insurance_document_edit', methods: ['GET', 'POST'])]
    public function editDocument(
        Request $request, 
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        VehicleInsurance $vehicleInsurance,
        #[MapEntity(id: 'documentId')] Document $document,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager, 
            currentUser: $currentUser, 
            vehicle: $vehicle, 
            vehicleInsurance: $vehicleInsurance,
            document: $document,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInsurance->getId()],
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

            return $this->redirectToRoute('app_vehicle_insurance_show', [
                'vehicleId' => $vehicle->getId(),
                'id' => $vehicleInsurance->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('document/edit.html.twig', [
            'document' => $document,
            'form' => $form,
            'entity' => $vehicleInsurance,
            'subtitle' => 'Assurance : ' . ucfirst($vehicleInsurance->getProviderName()),
        ]);
    }

    #[Route('/{vehicleId}/insurance/{id}/document/{documentId}', name: 'app_vehicle_insurance_document_delete', methods: ['POST'])]
    public function deleteDocument(
        Request $request, 
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        VehicleInsurance $vehicleInsurance,
        #[MapEntity(id: 'documentId')] Document $document,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
        DocumentManager $documentManager,
    ): Response {
        $response = $this->checkAthorization(
            vehicleManager: $vehicleManager, 
            currentUser: $currentUser, 
            vehicle: $vehicle, 
            vehicleInsurance: $vehicleInsurance,
            document: $document,
            params: ["vehicleId" => $vehicle->getId(), "id" => $vehicleInsurance->getId()],
            delete: true,
        );

        if ($response) {
            return $response;
        }

        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->getPayload()->getString('_token'))) {
            $documentManager->softDelete($document);

            $this->addFlash('success', 'Document supprimé avec succès.');
        }

        return $this->redirectToRoute('app_vehicle_insurance_show', [
            'vehicleId' => $vehicle->getId(),
            'id' => $vehicleInsurance->getId(),
        ], Response::HTTP_SEE_OTHER);
    }

    private function checkAthorization(
        VehicleManager $vehicleManager,
        User $currentUser,
        Vehicle $vehicle,
        VehicleInsurance $vehicleInsurance,
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
        if ($vehicleInsurance->isDeleted()) {
            $this->addFlash('warning', 'L\'assurance demandée a été supprimée. ressoPour plus d\'informations, contactez un administrateururce demandée.');
            return $this->redirectToRoute('app_vehicle_insurance_index', $params, Response::HTTP_SEE_OTHER);
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
                return $this->redirectToRoute('app_vehicle_insurance_index', $params, Response::HTTP_SEE_OTHER);
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
