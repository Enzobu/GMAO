<?php

namespace App\Controller;

use App\Entity\Document;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/document')]
final class DocumentController extends AbstractController
{
    #[Route('/{id}', name: 'app_document_show', methods: ['GET'])]
    public function show(Document $document): Response
    {
        $filePath = $this->getParameter('documents_directory') . '/' . $document->getStoredFilename();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        return $this->file(
            $filePath,
            $document->getOriginalFilename(),
            ResponseHeaderBag::DISPOSITION_INLINE
        );
    }

    #[Route('/{id}', name: 'app_document_delete', methods: ['POST'])]
    public function delete(Request $request, Document $document, EntityManagerInterface $entityManager): Response
    {
        $_route = 'app_home';
        $_entity_id = null;

        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->getPayload()->getString('_token'))) {
            $_route = $request->request->get('_route');
            $_entity_id = $request->request->get('_entity_id');

            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('danger', 'Vous n\'avez pas les autorisations nécessaire pour supprimer un document. Veuillez contacter un administrateur');
    
                return $_entity_id ? 
                    $this->redirectToRoute($_route, ["id" => $_entity_id], Response::HTTP_SEE_OTHER) : 
                    $this->redirectToRoute($_route, [], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($document);
            $entityManager->flush();

            $this->addFlash('success', 'Document supprimé avec succès.');
        }

        return $_entity_id ? 
            $this->redirectToRoute($_route, ["id" => $_entity_id], Response::HTTP_SEE_OTHER) : 
            $this->redirectToRoute($_route, [], Response::HTTP_SEE_OTHER);
    }
}
