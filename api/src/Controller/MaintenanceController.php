<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\User;
use App\Enum\MaintenanceStatusEnum;
use App\Form\DocumentType;
use App\Form\MaintenanceType;
use App\Repository\DocumentRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\MaintenanceTypeRepository;
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

#[Route('/maintenance')]
final class MaintenanceController extends AbstractController
{
    use DocumentUploadTrait;
    use MileageWarningTrait;

    #[Route(name: 'app_maintenance_index', methods: ['GET'])]
    public function index(
        Request $request,
        MaintenanceRepository $maintenanceRepository,
        MaintenanceTypeRepository $maintenanceTypeRepository,
        VehicleRepository $vehicleRepository,
    ): Response {
        $vehicleId = $request->query->get('vehicle');
        $typeId = $request->query->get('type');
        $type = $typeId ? $maintenanceTypeRepository->find((int) $typeId) : null;
        $status = $request->query->get('status') ? MaintenanceStatusEnum::tryFrom((string) $request->query->get('status')) : null;
        $query = $request->query->get('q');
        $sort = (string) $request->query->get('sort', 'createdAt');
        $direction = (string) $request->query->get('direction', 'DESC');

        return $this->render('maintenance/index.html.twig', [
            'maintenances' => $maintenanceRepository->findByFilters(
                vehicleId: $vehicleId ? (int) $vehicleId : null,
                type: $type,
                status: $status,
                query: $query,
                sort: $sort,
                direction: $direction,
            ),
            'vehicles' => $vehicleRepository->findAllNotDeleted(),
            'maintenance_types' => $maintenanceTypeRepository->findAllNotDeleted(),
            'maintenance_statuses' => MaintenanceStatusEnum::cases(),
            'selectedVehicleId' => $vehicleId,
            'selectedType' => $type?->getId(),
            'selectedStatus' => $status?->value,
            'searchQuery' => $query,
            'selectedSort' => $sort,
            'selectedDirection' => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
        ]);
    }

    #[Route('/new', name: 'app_maintenance_new', methods: ['GET', 'POST'])]
    public function new(): Response
    {
        $this->addFlash('warning', 'La création d’un entretien se fait depuis la fiche du véhicule concerné.');

        return $this->redirectToRoute('app_maintenance_index', [], Response::HTTP_SEE_OTHER);
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
        VehicleManager $vehicleManager,
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

        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Seul un administrateur peut modifier un entretien depuis la liste globale.');

            return $this->redirectToRoute('app_maintenance_show', [
                'id' => $maintenance->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        $oldVehicle = $maintenance->getVehicle();
        $oldMileage = $this->getMaintenanceMileageContribution($maintenance);
        $oldVehicleLastMileage = $oldVehicle?->getLastMileage();
        $mileageWarning = null;

        $form = $this->createForm(MaintenanceType::class, $maintenance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newVehicle = $maintenance->getVehicle();
            $newMileage = $this->getMaintenanceMileageContribution($maintenance);
            $warning = $vehicleManager->buildEventMileageWarning(
                oldVehicle: $oldVehicle,
                oldMileage: $oldMileage,
                newVehicle: $newVehicle,
                newMileage: $newMileage,
            );

            if ($this->shouldStopForMileageWarning($request, $form, $warning, $mileageWarning)) {
                return $this->render('maintenance/edit.html.twig', [
                    'maintenance' => $maintenance,
                    'form' => $form,
                    'mileage_warning' => $mileageWarning,
                ]);
            }

            foreach ($maintenance->getMaintenanceParts() as $maintenancePart) {
                $maintenancePart->setMaintenance($maintenance);
            }

            $entityManager->flush();

            if ($vehicleManager->syncAfterEventMileageChange($oldVehicle, $oldMileage, $newVehicle, $newMileage, $oldVehicleLastMileage)) {
                $entityManager->flush();
            }

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
        VehicleManager $vehicleManager,
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

        $oldVehicle = $maintenance->getVehicle();
        $oldMileage = $this->getMaintenanceMileageContribution($maintenance);
        $oldVehicleLastMileage = $oldVehicle?->getLastMileage();

        if (
            $this->isCsrfTokenValid(
                'delete' . $maintenance->getId(),
                $request->getPayload()->getString('_token')
            )
        ) {
            $maintenance->setIsDeleted(true);
            $entityManager->flush();

            if ($vehicleManager->syncAfterEventMileageChange($oldVehicle, $oldMileage, null, null, $oldVehicleLastMileage)) {
                $entityManager->flush();
            }

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

        $response = $this->persistUploadedDocumentFromForm(
            $document,
            $form,
            $entityManager,
            static fn (Document $document) => $document->setMaintenance($maintenance),
            fn () => $this->renderDocumentForm('document/new.html.twig', $document, $form, $maintenance, 'Maintenance du véhicule : ' . ucfirst($maintenance->getVehicle()->getName()) . ' ・ ' . strtoupper($maintenance->getVehicle()->getRegistration())),
            fn () => $this->redirectToRoute('app_maintenance_show', [
                'id' => $maintenance->getId(),
            ], Response::HTTP_SEE_OTHER),
        );

        if ($response) {
            return $response;
        }

        return $this->renderDocumentForm('document/new.html.twig', $document, $form->createView(), $maintenance, 'Véhicule : ' . ucfirst($maintenance->getVehicle()->getName()) . ' ・ ' . strtoupper($maintenance->getVehicle()->getRegistration()));
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
            $this->flushDocumentUpdate($entityManager, $document, $oldName, $oldDescription);

            return $this->redirectToRoute('app_maintenance_show', [
                'id' => $maintenance->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->renderDocumentForm('document/edit.html.twig', $document, $form, $maintenance, 'Véhicule : ' . ucfirst($maintenance->getVehicle()->getName()) . ' ・ ' . strtoupper($maintenance->getVehicle()->getRegistration()));
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

        $this->softDeleteDocumentWhenCsrfIsValid($request, $documentManager, $document);

        return $this->redirectToRoute('app_maintenance_show', ["id" => $maintenance->getId()], Response::HTTP_SEE_OTHER);
    }

    private function checkAuthorization(
        User $currentUser,
        Maintenance $maintenance,
        ?Document $document = null,
        ?array $params = [],
        bool $delete = false,
        bool $update = false,
    ): ?Response {
        # -------------------- Authization --------------------
        if (!$this->isGranted('ROLE_ADMIN') && $maintenance->getVehicle()?->getUser() != $currentUser) {
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
            if (!$this->isGranted('ROLE_ADMIN') && ($maintenance->getVehicle()->getUser() != $currentUser)) {
                $this->addFlash('danger', 'Vous ne pouvez pas ajouter un document sur la ressource demandée. Pour plus d\'informations, contactez un administrateur.');
                return $this->redirectToRoute('app_maintenance_index', [], Response::HTTP_SEE_OTHER);
            }
            if ($document->isDeleted()) {
                return $this->redirectIfDocumentIsDeleted($document, 'app_maintenance_index', [], 'Le document a été supprimé. Pour plus d\'informations, contactez un administrateur.');
            }
            if ($delete) {
                $response = $this->redirectUnlessAdmin('app_maintenance_show', $params ?? [], 'Vous n\'avez pas les autorisations nécessaires pour supprimer un document. Veuillez contacter un administrateur');

                if ($response) {
                    return $response;
                }
            }
        }
        if ($delete) {
            $response = $this->redirectUnlessAdmin('app_maintenance_show', $params ?? [], 'Vous n\'avez pas les autorisations nécessaires pour supprimer une maintenance. Veuillez contacter un administrateur');

            if ($response) {
                return $response;
            }
        }
        # -----------------------------------------------------
        return null;
    }
}
