<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Part;
use App\Form\DocumentType;
use App\Form\PartFormType;
use App\Repository\DocumentRepository;
use App\Repository\PartRepository;
use App\Repository\PartTypeRepository;
use App\Repository\VehicleRepository;
use App\Service\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/part')]
final class PartController extends AbstractController
{
    use DocumentUploadTrait;

    private const DOCUMENT_TITLE_PREFIX = 'Pièce : ';

    #[Route(name: 'app_part_index', methods: ['GET'])]
    public function index(
        Request $request,
        PartRepository $partRepository,
        VehicleRepository $vehicleRepository,
        PartTypeRepository $partTypeRepository,
    ): Response {
        $this->checkAthorization(
            roleAdminRequired: false,
        );

        $vehicleId = $request->query->get('vehicle');
        $partTypeId = $request->query->get('partType');

        return $this->render('part/index.html.twig', [
            'parts' => $partRepository->findByFilters(
                $vehicleId ? (int) $vehicleId : null,
                $partTypeId ? (int) $partTypeId : null,
            ),
            'vehicles' => $vehicleRepository->findBy([], ['name' => 'ASC']),
            'partTypes' => $partTypeRepository->findBy([], ['name' => 'ASC']),
            'selectedVehicleId' => $vehicleId,
            'selectedPartTypeId' => $partTypeId,
        ]);
    }

    #[Route('/new', name: 'app_part_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $part = new Part();

        $response = $this->checkAthorization(
            part: $part,
        );

        if ($response) {
            return $response;
        }

        $form = $this->createForm(PartFormType::class, $part);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($part);
            $entityManager->flush();

            return $this->redirectToRoute('app_part_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('part/new.html.twig', [
            'part' => $part,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_part_show', methods: ['GET'])]
    public function show(
        Part $part,
        DocumentRepository $documentRepository,
    ): Response {
        $response = $this->checkAthorization(
            roleAdminRequired: false,
            part: $part,
        );

        if ($response) {
            return $response;
        }

        return $this->render('part/show.html.twig', [
            'part' => $part,
            'part_document' => $documentRepository->findByPart(part: $part, deleted: false),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_part_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Part $part, EntityManagerInterface $entityManager): Response
    {
        $response = $this->checkAthorization(
            part: $part,
        );

        if ($response) {
            return $response;
        }

        $form = $this->createForm(PartFormType::class, $part);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Modifications enregistrées.');

            return $request->query->get('show') == 'true' ?
                $this->redirectToRoute('app_part_show', [
                    "id" => $part->getId(),
                ], Response::HTTP_SEE_OTHER) :
                $this->redirectToRoute('app_part_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('part/edit.html.twig', [
            'part' => $part,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_part_delete', methods: ['POST'])]
    public function delete(Request $request, Part $part, EntityManagerInterface $entityManager): Response
    {
        $response = $this->checkAthorization(
            part: $part,
        );

        if ($response) {
            return $response;
        }

        if ($this->isCsrfTokenValid('delete'.$part->getId(), $request->getPayload()->getString('_token'))) {
            $part->setIsDeleted(true);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_part_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/add-stock', name: 'app_part_add_stock', methods: ['POST'])]
    public function addStock(
        Request $request,
        Part $part,
        EntityManagerInterface $entityManager,
    ): Response {
        $response = $this->checkAthorization(part: $part);

        if ($response) {
            return $response;
        }

        if (!$this->isCsrfTokenValid('add_stock'.$part->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_part_index', [], Response::HTTP_SEE_OTHER);
        }

        $quantityToAdd = max(0, (int) $request->request->get('quantity'));

        if ($quantityToAdd <= 0) {
            $this->addFlash('warning', 'Veuillez saisir une quantité supérieure à 0.');

            return $this->redirectToRoute('app_part_index', [], Response::HTTP_SEE_OTHER);
        }

        $part->setQuantity($part->getQuantity() + $quantityToAdd);

        $entityManager->flush();

        $this->addFlash('success', sprintf(
            '%d pièce%s ajoutée%s au stock.',
            $quantityToAdd,
            $quantityToAdd > 1 ? 's' : '',
            $quantityToAdd > 1 ? 's' : ''
        ));

        return $this->redirectToRoute('app_part_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/document/new', name: 'app_part_document_new', methods: ['GET', 'POST'])]
    public function newDocument(
        Request $request,
        EntityManagerInterface $entityManager,
        Part $part,
        SluggerInterface $slugger,
    ): Response {
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        $response = $this->persistUploadedDocumentFromForm(
            $document,
            $form,
            $entityManager,
            static fn (Document $document) => $document->setPart($part),
            fn () => $this->renderDocumentForm('document/new.html.twig', $document, $form, $part, self::DOCUMENT_TITLE_PREFIX . $part->getPartType()->getName()),
            fn () => $this->redirectToRoute('app_part_show', ["id" => $part->getId()], Response::HTTP_SEE_OTHER),
            $slugger,
        );

        if ($response) {
            return $response;
        }

        return $this->renderDocumentForm('document/new.html.twig', $document, $form, $part, self::DOCUMENT_TITLE_PREFIX . $part->getPartType()->getName());
    }

    #[Route('/{id}/document/{documentId}/edit', name: 'app_part_document_edit', methods: ['GET', 'POST'])]
    public function editDocument(
        Request $request,
        EntityManagerInterface $entityManager,
        Part $part,
        #[MapEntity(mapping: ['documentId' => 'publicId'])] Document $document,
    ): Response {
        $response = $this->checkAthorization(
            document: $document,
            edit: true,
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

            return $this->redirectToRoute('app_part_show', ["id" => $part->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderDocumentForm('document/edit.html.twig', $document, $form, $part, self::DOCUMENT_TITLE_PREFIX . $part->getPartType()->getName());
    }

    #[Route('/{id}/document/{documentId}', name: 'app_part_document_delete', methods: ['POST'])]
    public function deleteDocument(
        Request $request,
        DocumentManager $documentManager,
        Part $part,
        #[MapEntity(mapping: ['documentId' => 'publicId'])] Document $document,
    ): Response {
        $response = $this->checkAthorization(
            document: $document,
            delete: true,
        );

        if ($response) {
            return $response;
        }

        $this->softDeleteDocumentWhenCsrfIsValid($request, $documentManager, $document);

        return $this->redirectToRoute('app_part_show', ["id" => $part->getId()], Response::HTTP_SEE_OTHER);
    }

    private function checkAthorization(
        bool $roleAdminRequired = true,
        ?Part $part = null,
        ?Document $document = null,
        bool $delete = false,
        bool $edit = false,
    ): ?Response {
        # -------------------- Authization --------------------
        if ($delete || $edit) {
            $response = $this->redirectUnlessAdmin('app_part_index', [], 'Vous n\'avez pas les autorisations nécessaires pour supprimer un document. Veuillez contacter un administrateur');

            if ($response) {
                return $response;
            }
        }
        if ($roleAdminRequired) {
            $response = $this->redirectUnlessAdmin('app_part_index', [], 'Vous n\'avez pas les autorisations nécessaires pour accéder à la ressource demandée. Pour plus d\'information, contacter un administrateur');

            if ($response) {
                return $response;
            }
        }
        if ($part) {
            if ($part->isDeleted()) {
                $this->addFlash('danger', 'La ligne de stock à été supprimée. ressoPour plus d\'informations, contactez un administrateururce demandée.');
                return $this->redirectToRoute('app_part_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('warning', 'Vous avez un accès en lecture seule à la ressource demandée. ressoPour plus d\'informations, contactez un administrateururce demandée.');
        }
        if ($document) {
            $response = $this->redirectIfDocumentIsDeleted($document, 'app_part_index', [], 'Le document a été supprimé. Pour plus d\'informations, contactez un administrateur.');

            if ($response) {
                return $response;
            }
        }
        # -----------------------------------------------------
        return null;
    }
}
