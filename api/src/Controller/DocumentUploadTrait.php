<?php

namespace App\Controller;

use App\Entity\Document;
use App\Service\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;

trait DocumentUploadTrait
{
    protected function storeUploadedDocument(
        Document $document,
        UploadedFile $uploadedFile,
        string $documentsDirectory,
        ?SluggerInterface $slugger = null,
    ): void {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower($uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension() ?: 'bin');
        $mimeType = $uploadedFile->getMimeType();
        $size = $uploadedFile->getSize();
        $storedFilename = $slugger === null
            ? sprintf('%s.%s', bin2hex(random_bytes(16)), $extension)
            : sprintf('%s-%s.%s', $slugger->slug($originalFilename), uniqid(), $extension);

        $uploadedFile->move($documentsDirectory, $storedFilename);

        $document
            ->setOriginalFilename($uploadedFile->getClientOriginalName())
            ->setStoredFilename($storedFilename)
            ->setMimeType($mimeType)
            ->setSize($size)
            ->setExtension($extension)
        ;

        if (!$document->getName()) {
            $document->setName($originalFilename);
        }
    }

    protected function persistUploadedDocumentFromForm(
        Document $document,
        FormInterface $form,
        EntityManagerInterface $entityManager,
        callable $attachDocument,
        callable $renderUploadError,
        callable $successResponse,
        ?SluggerInterface $slugger = null,
    ): ?Response {
        if (!$form->isSubmitted() || !$form->isValid()) {
            return null;
        }

        $uploadedFile = $form->get('file')->getData();

        if ($uploadedFile === null) {
            return null;
        }

        try {
            $this->storeUploadedDocument($document, $uploadedFile, $this->getParameter('documents_directory'), $slugger);
        } catch (FileException $e) {
            $this->addFlash('danger', 'Le fichier n’a pas pu être envoyé.');

            return $renderUploadError();
        }

        $attachDocument($document);
        $entityManager->persist($document);
        $entityManager->flush();

        $this->addFlash('success', 'Le document a bien été ajouté.');

        return $successResponse();
    }

    protected function flushDocumentUpdate(
        EntityManagerInterface $entityManager,
        Document $document,
        ?string $oldName,
        ?string $oldDescription,
    ): void {
        $entityManager->flush();

        if ($oldName !== $document->getName() || $oldDescription !== $document->getDescription()) {
            $this->addFlash('success', 'Le document a bien été modifié.');
        } else {
            $this->addFlash('warning', 'Le document ne comporte aucune modification.');
        }
    }

    protected function softDeleteDocumentWhenCsrfIsValid(
        Request $request,
        DocumentManager $documentManager,
        Document $document,
    ): void {
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->getPayload()->getString('_token'))) {
            $documentManager->softDelete($document);

            $this->addFlash('success', 'Document supprimé avec succès.');
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function redirectIfDocumentIsDeleted(
        Document $document,
        string $route,
        array $params = [],
        string $message = 'Le document a été supprimé. ressoPour plus d\'informations, contactez un administrateururce demandée.',
    ): ?Response
    {
        if (!$document->isDeleted()) {
            return null;
        }

        $this->addFlash('danger', $message);

        return $this->redirectToRoute($route, $params, Response::HTTP_SEE_OTHER);
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function redirectUnlessAdmin(string $route, array $params, string $message): ?Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return null;
        }

        $this->addFlash('danger', $message);

        return $this->redirectToRoute($route, $params, Response::HTTP_SEE_OTHER);
    }
}
