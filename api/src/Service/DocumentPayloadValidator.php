<?php

namespace App\Service;

use App\Entity\Document;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class DocumentPayloadValidator
{
    private const MAX_FILE_SIZE = 8 * 1024 * 1024;

    public function uploadedFile(Request $request): UploadedFile
    {
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            throw new UnprocessableEntityHttpException('Le fichier est obligatoire.');
        }

        if ($file->getSize() !== false && $file->getSize() > self::MAX_FILE_SIZE) {
            throw new UnprocessableEntityHttpException('Fichier trop volumineux. Max 8 Mo.');
        }

        return $file;
    }

    public function documentName(Request $request, UploadedFile $file): string
    {
        $name = trim((string) $request->request->get('name', ''));

        if ($name !== '') {
            return $name;
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        return $originalName ?: 'Document';
    }

    public function requestDescription(Request $request): ?string
    {
        return $this->nullableString($request->request->get('description'));
    }

    public function applyMetadata(Request $request, Document $document): void
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON payload');
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new UnprocessableEntityHttpException('Le nom est obligatoire.');
        }

        $document
            ->setName($name)
            ->setDescription($this->nullableString($payload['description'] ?? null));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
