<?php

namespace App\Service;

use App\Entity\Document;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DocumentFileResponseFactory
{
    public function __construct(private DocumentManager $documentManager) {}

    public function create(Document $document, string $disposition): BinaryFileResponse
    {
        if (!$this->documentManager->fileExists($document)) {
            throw new NotFoundHttpException('Fichier introuvable.');
        }

        $response = new BinaryFileResponse($this->documentManager->getAbsolutePath($document));
        $response->setContentDisposition($disposition, $this->documentManager->getDownloadFilename($document));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        if ($document->getMimeType()) {
            $response->headers->set('Content-Type', $document->getMimeType());
        }

        return $response;
    }

    public function inline(Document $document): BinaryFileResponse
    {
        return $this->create($document, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    public function attachment(Document $document): BinaryFileResponse
    {
        return $this->create($document, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }
}
