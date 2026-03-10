<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Enum\MaintenanceStatusEnum;
use App\Form\DocumentType;
use App\Form\VehicleType;
use App\Repository\VehicleInspectionRepository;
use App\Repository\VehicleInsuranceRepository;
use App\Repository\VehicleMaintenanceRepository;
use App\Repository\VehicleRepository;
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
final class VehicleController extends AbstractController
{
    #[Route(name: 'app_vehicle_index', methods: ['GET'])]
    public function index(
        VehicleRepository $vehicleRepository,
        #[CurrentUser] User $currentUser,
    ): Response {
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $vehicles = $isAdmin ? 
            $vehicleRepository->findAllNotDeleted() :
            $vehicleRepository->findAllNotDeletedByUser($currentUser)
        ;

        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicles,
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
        VehicleMaintenanceRepository $vehicleMaintenanceRepository,
        #[CurrentUser] User $currentUser,
    ): Response
    {
        $this->checkAthorization($vehicleManager, $currentUser, $vehicle);


        $insurance = $vehicleInsuranceRepository->findBy([
            "vehicle" => $vehicle,
        ], ['startDate' => 'DESC']);

        $inspection = $vehicleInspectionRepository->findBy([        
            "vehicle" => $vehicle,
        ], ['inspectionDate' => 'DESC']);
        $maintenance = $vehicleMaintenanceRepository->findBy([
            "vehicle" => $vehicle,
            "status" => MaintenanceStatusEnum::ToDo,
        ], ['nextDueDate' => 'ASC', 'createdAt' => 'ASC']);

        return $this->render('vehicle/show.html.twig', [
            'vehicle' => $vehicle,
            'insurance' => $insurance[0] ?? null,
            'inspection' => $inspection[0] ?? null,
            'maintenance' => $maintenance,
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
        $this->checkAthorization($vehicleManager, $currentUser, $vehicle);

        $form = $this->createForm(VehicleType::class, $vehicle, ['edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

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
    public function delete(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
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
        SluggerInterface $slugger,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $this->checkAthorization($vehicleManager, $currentUser, $vehicle);

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
                        'subtitle' => 'Véhicule : ' . ucfirst($vehicle->getName()) . ' ・ ' . strtoupper($vehicle->getRegistration()),
                        'entity' => $vehicle,
                    ]);
                }

                $document
                    ->setVehicle($vehicle)
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

                return $this->redirectToRoute('app_vehicle_show', [
                    'id' => $vehicle->getId(),
                ], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('document/new.html.twig', [
            'document' => $document,
            'form' => $form->createView(),
            'subtitle' => 'Véhicule : ' . ucfirst($vehicle->getName()) . ' ・ ' . strtoupper($vehicle->getRegistration()),
            'entity' => $vehicle,
        ]);
    }

    #[Route('/{id}/document/{documentId}/edit', name: 'app_vehicle_document_edit', methods: ['GET', 'POST'])]
    public function editDocument(
        Request $request, 
        EntityManagerInterface $entityManager,
        Vehicle $vehicle,
        #[MapEntity(id: 'documentId')] Document $document,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $this->checkAthorization($vehicleManager, $currentUser, $vehicle);

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

            return $this->redirectToRoute('app_vehicle_show', [
                'id' => $vehicle->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('document/edit.html.twig', [
            'document' => $document,
            'form' => $form,
            'entity' => $vehicle,
            'subtitle' => 'Véhicule : ' . ucfirst($vehicle->getName()) . ' ・ ' . strtoupper($vehicle->getRegistration()),
        ]);
    }

    #[Route('{id}/document/{documentId}', name: 'app_vehicle_document_delete', methods: ['POST'])]
    public function deleteDocument(
        Request $request,
        Vehicle $vehicle,
        #[MapEntity(id: 'documentId')] Document $document, 
        EntityManagerInterface $entityManager,
        VehicleManager $vehicleManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $this->checkAthorization($vehicleManager, $currentUser, $vehicle);

        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->getPayload()->getString('_token'))) {

            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('danger', 'Vous n\'avez pas les autorisations nécessaire pour supprimer un document. Veuillez contacter un administrateur');
    
                return $this->redirectToRoute('app_vehicle_show', ["id" => $vehicle->getId()], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($document);
            $entityManager->flush();

            $this->addFlash('success', 'Document supprimé avec succès.');
        }

        return $this->redirectToRoute('app_vehicle_show', ["id" => $vehicle->getId()], Response::HTTP_SEE_OTHER);
    }

    private function checkAthorization(
        VehicleManager $vehicleManager,
        User $currentUser,
        Vehicle $vehicle,
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
        # -----------------------------------------------------
        return null;
    }
}
