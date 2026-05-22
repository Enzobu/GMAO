<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\User;
use App\Form\DocumentType;
use App\Form\MaintenanceType;
use App\Repository\DocumentRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\VehicleRepository;
use App\Service\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/maintenance')]
final class MaintenanceController extends AbstractController
{
    #[Route(name: 'app_maintenance_index', methods: ['GET'])]
    public function index(
        Request $request,
        MaintenanceRepository $maintenanceRepository,
        VehicleRepository $vehicleRepository,
    ): Response {
        $vehicleId = $request->query->get('vehicle');

        return $this->render('maintenance/index.html.twig', [
            'maintenances' => $maintenanceRepository->findByFilters(
                $vehicleId ? (int) $vehicleId : null,
            ),
            'vehicles' => $vehicleRepository->findAllNotDeleted(),
            'selectedVehicleId' => $vehicleId,
        ]);
    }

    #[Route('/new', name: 'app_maintenance_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $maintenance = new Maintenance();

        $response = $this->checkAuthorization(
            currentUser: $currentUser,
            maintenance: $maintenance,
            params: ["id" => $maintenance->getId()],
            new: true,
        );

        if ($response) {
            return $response;
        }

        $form = $this->createForm(MaintenanceType::class, $maintenance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($maintenance->getMaintenanceParts() as $maintenancePart) {
                $maintenancePart->setMaintenance($maintenance);
            }

            $entityManager->persist($maintenance);
            $entityManager->flush();

            $this->addFlash('success', 'L’entretien a bien été créé.');

            return $this->redirectToRoute('app_maintenance_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('maintenance/new.html.twig', [
            'maintenance' => $maintenance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_maintenance_show', methods: ['GET'])]
    public function show(
        Maintenance $maintenance,
        DocumentRepository $documentRepository,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAuthorization(
            currentUser: $currentUser,
            maintenance: $maintenance,
            params: ["id" => $maintenance->getId()],
        );

        if ($response) {
            return $response;
        }

        return $this->render('maintenance/show.html.twig', [
            'maintenance' => $maintenance,
            'maintenance_document' => $documentRepository->findByMaintenance(maintenance: $maintenance, deleted: false),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_maintenance_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Maintenance $maintenance, 
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAuthorization(
            currentUser: $currentUser,
            maintenance: $maintenance,
            params: ["id" => $maintenance->getId()],
            update: true,
        );

        if ($response) {
            return $response;
        }

        $form = $this->createForm(MaintenanceType::class, $maintenance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($maintenance->getMaintenanceParts() as $maintenancePart) {
                $maintenancePart->setMaintenance($maintenance);
            }

            $entityManager->flush();

            $this->addFlash('success', 'L’entretien a bien été modifié.');

            return $request->query->get('show') == 'true' ?
                $this->redirectToRoute('app_maintenance_show', [
                    "id" => $maintenance->getId(),
                ], Response::HTTP_SEE_OTHER) :
                $this->redirectToRoute('app_maintenance_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('maintenance/edit.html.twig', [
            'maintenance' => $maintenance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_maintenance_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Maintenance $maintenance, 
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAuthorization(
            currentUser: $currentUser,
            maintenance: $maintenance,
            delete: true,
            params: ["id" => $maintenance->getId()],
        );

        if ($response) {
            return $response;
        }

        if (
            $this->isCsrfTokenValid(
                'delete' . $maintenance->getId(),
                $request->getPayload()->getString('_token')
            )
        ) {
            $maintenance->setIsDeleted(true);
            $entityManager->flush();

            $this->addFlash('success', 'L’entretien a bien été supprimé.');
        }

        return $this->redirectToRoute('app_maintenance_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/document/new', name: 'app_maintenance_document_new', methods: ['GET', 'POST'])]
    public function newDocument(
        Request $request,
        EntityManagerInterface $entityManager,
        Maintenance $maintenance,
        #[CurrentUser] User $currentUser,
    ): Response {
        $document = new Document();

        $response = $this->checkAuthorization(
            currentUser: $currentUser,
            maintenance: $maintenance,
            params: ["id" => $maintenance->getId()],
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
                $extension = strtolower($uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension() ?: 'bin');

                $mimeType = $uploadedFile->getMimeType();
                $size = $uploadedFile->getSize();

                $storedFilename = sprintf('%s.%s', bin2hex(random_bytes(16)), $extension);

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
                        'subtitle' => 'Maintenance du véhicule : ' . ucfirst($maintenance->getVehicle()->getName()) . ' ・ ' . strtoupper($maintenance->getVehicle()->getRegistration()),
                        'entity' => $maintenance,
                    ]);
                }

                $document
                    ->setMaintenance($maintenance)
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

                return $this->redirectToRoute('app_maintenance_show', [
                    'id' => $maintenance->getId(),
                ], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('document/new.html.twig', [
            'document' => $document,
            'form' => $form->createView(),
            'subtitle' => 'Véhicule : ' . ucfirst($maintenance->getVehicle()->getName()) . ' ・ ' . strtoupper($maintenance->getVehicle()->getRegistration()),
            'entity' => $maintenance,
        ]);
    }

    #[Route('/{id}/document/{documentId}/edit', name: 'app_maintenance_document_edit', methods: ['GET', 'POST'])]
    public function editDocument(
        Request $request, 
        EntityManagerInterface $entityManager,
        Maintenance $maintenance,
        #[MapEntity(mapping: ['documentId' => 'publicId'])] Document $document,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAuthorization(
            currentUser: $currentUser,
            maintenance: $maintenance,
            params: ["id" => $maintenance->getId()],
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
            $entityManager->flush();

            $name = $document->getName();
            $description = $document->getDescription();

            if (($oldName != $name) || ($oldDescription != $description)) {
                $this->addFlash('success', 'Le document a bien été modifié.');
            } else {
                $this->addFlash('warning', 'Le document ne comporte aucune modification.');
            }

            return $this->redirectToRoute('app_maintenance_show', [
                'id' => $maintenance->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('document/edit.html.twig', [
            'document' => $document,
            'form' => $form,
            'entity' => $maintenance,
            'subtitle' => 'Véhicule : ' . ucfirst($maintenance->getVehicle()->getName()) . ' ・ ' . strtoupper($maintenance->getVehicle()->getRegistration()),
        ]);
    }

    #[Route('{id}/document/{documentId}', name: 'app_maintenance_document_delete', methods: ['POST'])]
    public function deleteDocument(
        Request $request,
        Maintenance $maintenance,
        #[MapEntity(mapping: ['documentId' => 'publicId'])] Document $document,
        DocumentManager $documentManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $response = $this->checkAuthorization(
            currentUser: $currentUser,
            maintenance: $maintenance,
            delete: true,
            params: ["id" => $maintenance->getId()],
            document: $document,
        );

        if ($response) {
            return $response;
        }

        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->getPayload()->getString('_token'))) {
            $documentManager->softDelete($document);

            $this->addFlash('success', 'Document supprimé avec succès.');
        }

        return $this->redirectToRoute('app_maintenance_show', ["id" => $maintenance->getId()], Response::HTTP_SEE_OTHER);
    }

    private function checkAuthorization(
        User $currentUser,
        Maintenance $maintenance,
        ?Document $document = null,
        ?Array $params = [],
        bool $delete = false,
        bool $update = false,
        bool $new = false,
    ): ?Response {
        # -------------------- Authization --------------------
        if($new) {
            return null;
        }
        if (!($maintenance->getVehicle()?->getUser() == $currentUser)) {
            if ($update) {
                $this->addFlash('danger', 'Vous ne pouvez pas modifier la ressource demandée. Pour plus d\'informations, contactez un administrateur.');
            } else {
                $this->addFlash('warning', 'Vous avez un accès en lecture seule à la ressource demandée. Pour plus d\'informations, contactez un administrateur.');
            }
            if ($update) {
                return $this->redirectToRoute('app_maintenance_index', [], Response::HTTP_SEE_OTHER);
                }
            }
        if ($maintenance->isDeleted()) {
            $this->addFlash('danger', 'La maintenance a été supprimé. Pour plus d\'informations, contactez un administrateur.');
            return $this->redirectToRoute('app_maintenance_index', [], Response::HTTP_SEE_OTHER);
        }
        if ($document) {
            if (!$maintenance->getVehicle()->getUser() == $currentUser) {
                $this->addFlash('danger', 'Vous ne pouvez pas ajouter un document sur la ressource demandée. Pour plus d\'informations, contactez un administrateur.');
                return $this->redirectToRoute('app_maintenance_index', [], Response::HTTP_SEE_OTHER);
            }
            if ($document->isDeleted()) {
                $this->addFlash('danger', 'Le document a été supprimé. Pour plus d\'informations, contactez un administrateur.');
                return $this->redirectToRoute('app_maintenance_index', [], Response::HTTP_SEE_OTHER);
            }
            if ($delete) {
                if (!$this->isGranted('ROLE_ADMIN')) {
                    $this->addFlash('danger', 'Vous n\'avez pas les autorisations nécessaires pour supprimer un document. Veuillez contacter un administrateur');
                    return $this->redirectToRoute('app_maintenance_show', $params, Response::HTTP_SEE_OTHER);
                }
            }
        }
        if ($delete) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('danger', 'Vous n\'avez pas les autorisations nécessaires pour supprimer une maintenance. Veuillez contacter un administrateur');
                return $this->redirectToRoute('app_maintenance_show', $params, Response::HTTP_SEE_OTHER);
            }
        }
        # -----------------------------------------------------
        return null;
    }
}
