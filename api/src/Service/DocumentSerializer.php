<?php

namespace App\Service;

use App\Entity\Document;

final readonly class DocumentSerializer
{
    /** @return array<string, mixed> */
    public function serialize(Document $document): array
    {
        return [
            'id' => $document->getId(),
            'publicId' => $document->getPublicId(),
            'name' => $document->getName(),
            'description' => $document->getDescription(),
            'originalFilename' => $document->getOriginalFilename(),
            'mimeType' => $document->getMimeType(),
            'size' => $document->getSize(),
            'extension' => $document->getExtension(),
            'createdAt' => $document->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $document->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
