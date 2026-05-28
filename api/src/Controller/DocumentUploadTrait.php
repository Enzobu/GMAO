<?php

namespace App\Controller;

use App\Entity\Document;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
}
