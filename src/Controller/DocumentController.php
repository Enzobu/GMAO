<?php

namespace App\Controller;

use App\Entity\Document;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
