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
    ): Response {
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
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $vehicleInsurance = new VehicleInsurance();
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
        $this->checkAthorization($vehicleManager, $currentUser, $vehicle, $vehicleInsurance);

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
        $this->checkAthorization($vehicleManager, $currentUser, $vehicle, $vehicleInsurance);

        $form = $this->createForm(VehicleInsuranceType::class, $vehicleInsurance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

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
        $this->checkAthorization($vehicleManager, $currentUser, $vehicle, $vehicleInsurance);

        if ($this->isCsrfTokenValid('delete'.$vehicleInsurance->getId(), $request->getPayload()->getString('_token'))) {
            $vehicleInsurance->setIsDeleted(true);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Assurance supprimée avec succès.');

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
        $this->checkAthorization($vehicleManager, $currentUser, $vehicle, $vehicleInsurance);

        $document = new Document();
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
        $this->checkAthorization($vehicleManager, $currentUser, $vehicle, $vehicleInsurance);

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
        $this->checkAthorization($vehicleManager, $currentUser, $vehicle, $vehicleInsurance);
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Vous n\'avez pas les autorisations nécessaire pour supprimer un document. Veuillez contacter un administrateur');

            return $this->redirectToRoute('app_vehicle_insurance_show', [
                'vehicleId' => $vehicle->getId(),
                'id' => $vehicleInsurance->getId(),
            ], Response::HTTP_SEE_OTHER);
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
    ): ?Response {
        # -------------------- Authization --------------------
        if (!$vehicleManager->isAuthorized($currentUser, $vehicle)) {
            $this->addFlash('danger', 'Vous n\'avez pas accès à la ressource demandé. Pour plus d\'information, contactez un administrateur');
            return $this->redirectToRoute('app_vehicle_index');
        }
        if ($vehicle->isDeleted()) {
            $this->addFlash('danger', 'Le véhicule a été supprimé. Pour plus d\'information, contactez un administrateur');
            return $this->redirectToRoute('app_vehicle_index');
        }
        if ($vehicleInsurance->isDeleted()) {
            $this->addFlash('warning', 'L\'assurance demandée a été supprimée. Pour plus d\'information, contactez un administrateur');
            return $this->redirectToRoute('app_vehicle_insurance_index', [
                "vehicleId" => $vehicle->getId(),
            ], Response::HTTP_SEE_OTHER);
        }
        # -----------------------------------------------------
        return null;
    }
}
