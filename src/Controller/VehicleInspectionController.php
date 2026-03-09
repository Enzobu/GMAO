<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Form\DocumentType;
use App\Form\VehicleInspectionType;
use App\Repository\VehicleInspectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/vehicle')]
final class VehicleInspectionController extends AbstractController
{
    #[Route('/{vehicleId}/inspection', name: 'app_vehicle_inspection_index', methods: ['GET'])]
    public function index(
        VehicleInspectionRepository $vehicleInspectionRepository,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $inspections = $vehicleInspectionRepository->findBy([
            "vehicle" => $vehicle,
        ], ['inspectionDate' => 'DESC']);

        return $this->render('vehicle_inspection/index.html.twig', [
            'vehicle_inspections' => $inspections,
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/{vehicleId}/inspection/new', name: 'app_vehicle_inspection_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $vehicleInspection = new VehicleInspection();
        $form = $this->createForm(VehicleInspectionType::class, $vehicleInspection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $vehicleInspection->setVehicle($vehicle);

            $entityManager->persist($vehicleInspection);
            $entityManager->flush();

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
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        return $this->render('vehicle_inspection/show.html.twig', [
            'vehicle_inspection' => $vehicleInspection,
            'vehicle' => $vehicle,
        ]);
    }

    #[Route('/{vehicleId}/inspection/{id}/edit', name: 'app_vehicle_inspection_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        VehicleInspection $vehicleInspection,
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $form = $this->createForm(VehicleInspectionType::class, $vehicleInspection, ['edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

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
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $vehicleInspection->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($vehicleInspection);
            $entityManager->flush();

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
    ): Response {
        if ($vehicle->isDeleted()) {
            $this->addFlash('warning', 'Ce véhicule a été supprimé.');

            return $this->redirectToRoute('app_vehicle_index');
        }

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
        #[MapEntity(id: 'documentId')] Document $document,
    ): Response {
        if ($vehicle->isDeleted()) {
            $this->addFlash('warning', 'Ce véhicule a été supprimé.');

            return $this->redirectToRoute('app_vehicle_index');
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
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
        VehicleInspection $vehicleInspection,
        #[MapEntity(id: 'documentId')] Document $document,
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->getPayload()->getString('_token'))) {

            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('danger', 'Vous n\'avez pas les autorisations nécessaire pour supprimer un document. Veuillez contacter un administrateur');
    
                return $this->redirectToRoute('app_vehicle_inspection_show', [
                    'vehicleId' => $vehicle->getId(),
                    'id' => $vehicleInspection->getId(),
                ], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($document);
            $entityManager->flush();

            $this->addFlash('success', 'Document supprimé avec succès.');
        }

        return $this->redirectToRoute('app_vehicle_inspection_show', [
            'vehicleId' => $vehicle->getId(),
            'id' => $vehicleInspection->getId(),
        ], Response::HTTP_SEE_OTHER);
    }
}
