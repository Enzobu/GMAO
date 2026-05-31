<?php

namespace App\Controller\Api;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use App\Service\DocumentManager;
use App\Service\DocumentParentResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/{parentType}/{parentId}/documents', requirements: ['parentType' => 'users|vehicles|vehicle_insurances|vehicle_inspections|maintenances|parts', 'parentId' => '\\d+'])]
final class DocumentController extends AbstractController
{
    private const MAX_FILE_SIZE = 8 * 1024 * 1024;

    public function __construct(
        private readonly DocumentParentResolver $parents,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'api_documents_index', methods: ['GET'])]
    public function index(string $parentType, int $parentId): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $parent = $this->getParentOr404($parentType, $parentId);

        return $this->json(array_map(
            fn (Document $document): array => $this->serializeDocument($document),
            $this->parents->documents($parent),
        ));
    }

    #[Route('', name: 'api_documents_create', methods: ['POST'])]
    public function create(string $parentType, int $parentId, Request $request, DocumentManager $documentManager): JsonResponse
    {
        $parent = $this->getParentOr404($parentType, $parentId);
        $accessResponse = $this->denyUnlessCanManage($parent);
        if ($accessResponse instanceof JsonResponse) {
            return $accessResponse;
        }

        $uploadValidationResponse = $this->validateUploadedFile($request->files->get('file'));
        if ($uploadValidationResponse instanceof JsonResponse) {
            return $uploadValidationResponse;
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('file');

        $name = $this->documentName($request, $file);

        $document = $documentManager->createDocument($parent, $file, $name, $this->nullableString($request->request->get('description')));

        return $this->json($this->serializeDocument($document), Response::HTTP_CREATED);
    }

    #[Route('/{publicId}', name: 'api_documents_update', methods: ['PATCH'])]
    public function update(string $parentType, int $parentId, Request $request, #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document): JsonResponse
    {
        $parent = $this->getParentOr404($parentType, $parentId);
        $accessResponse = $this->denyUnlessCanManage($parent);
        if ($accessResponse instanceof JsonResponse) {
            return $accessResponse;
        }

        $ownershipResponse = $this->denyUnlessDocumentBelongsToParent($document, $parent);
        if ($ownershipResponse instanceof JsonResponse) {
            return $ownershipResponse;
        }

        $response = $this->validateAndApplyMetadata($request, $document);
        if (!$response instanceof JsonResponse) {
            $this->entityManager->flush();
            $response = $this->json($this->serializeDocument($document));
        }

        return $response;
    }

    #[Route('/{publicId}', name: 'api_documents_delete', methods: ['DELETE'])]
    public function delete(string $parentType, int $parentId, #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document, DocumentManager $documentManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['message' => 'Seul un administrateur peut archiver un document.'], Response::HTTP_FORBIDDEN);
        }

        $parent = $this->getParentOr404($parentType, $parentId);
        if ($document->isDeleted()) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        $this->denyUnlessDocumentBelongsToParentOrThrow($document, $parent);

        $documentManager->softDelete($document);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{publicId}/file', name: 'api_documents_file', methods: ['GET'])]
    public function showDocumentFile(string $parentType, int $parentId, #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document, DocumentManager $documentManager): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $parent = $this->getParentOr404($parentType, $parentId);
        $this->denyUnlessDocumentBelongsToParentOrThrow($document, $parent);

        return $this->buildDocumentFileResponse($document, $documentManager, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/{publicId}/download', name: 'api_documents_download', methods: ['GET'])]
    public function download(string $parentType, int $parentId, #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document, DocumentManager $documentManager): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $parent = $this->getParentOr404($parentType, $parentId);
        $this->denyUnlessDocumentBelongsToParentOrThrow($document, $parent);

        return $this->buildDocumentFileResponse($document, $documentManager, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    private function getParentOr404(string $parentType, int $parentId): User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part
    {
        $parent = $this->parents->resolve($parentType, $parentId);
        if ($parent === null || $this->parents->isDeleted($parent)) {
            throw $this->createNotFoundException('Ressource introuvable.');
        }

        return $parent;
    }

    private function denyUnlessCanManage(User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part $parent): ?JsonResponse
    {
        $response = null;
        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            $response = $this->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        } elseif (!$this->canManage($parent, $currentUser)) {
            $response = $this->json(['message' => 'Vous ne pouvez modifier que vos documents.'], Response::HTTP_FORBIDDEN);
        }

        return $response;
    }

    private function canManage(User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part $parent, User $currentUser): bool
    {
        $vehicle = $this->parents->owningVehicle($parent);

        return $this->isGranted('ROLE_ADMIN')
            || ($parent instanceof User && $parent->getId() === $currentUser->getId())
            || ($vehicle !== null && $vehicle->getUser()?->getId() === $currentUser->getId());
    }

    private function denyUnlessDocumentBelongsToParent(Document $document, User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part $parent): ?JsonResponse
    {
        if ($document->isDeleted() || !$this->parents->belongsToParent($document, $parent)) {
            return $this->json(['message' => 'Document introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return null;
    }

    private function denyUnlessDocumentBelongsToParentOrThrow(Document $document, User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part $parent): void
    {
        if ($document->isDeleted() || !$this->parents->belongsToParent($document, $parent)) {
            throw $this->createNotFoundException('Document introuvable.');
        }
    }

    private function validateUploadedFile(mixed $file): ?JsonResponse
    {
        $response = null;

        if (!$file instanceof UploadedFile) {
            $response = $this->json(['message' => 'Le fichier est obligatoire.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        } elseif ($file->getSize() !== false && $file->getSize() > self::MAX_FILE_SIZE) {
            $response = $this->json(['message' => 'Fichier trop volumineux. Max 8 Mo.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $response;
    }

    private function documentName(Request $request, UploadedFile $file): string
    {
        $name = trim((string) $request->request->get('name', ''));

        if ($name !== '') {
            return $name;
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        return $originalName ?: 'Document';
    }

    private function validateAndApplyMetadata(Request $request, Document $document): ?JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        $response = null;

        if (!is_array($payload)) {
            $response = $this->json(['message' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        } else {
            $name = trim((string) ($payload['name'] ?? ''));
            if ($name === '') {
                $response = $this->json(['message' => 'Le nom est obligatoire.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            } else {
                $document
                    ->setName($name)
                    ->setDescription($this->nullableString($payload['description'] ?? null));
            }
        }

        return $response;
    }

    private function buildDocumentFileResponse(Document $document, DocumentManager $documentManager, string $disposition): BinaryFileResponse
    {
        if (!$documentManager->fileExists($document)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $response = $this->file($documentManager->getAbsolutePath($document), $documentManager->getDownloadFilename($document), $disposition);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        if ($document->getMimeType()) {
            $response->headers->set('Content-Type', $document->getMimeType());
        }

        return $response;
    }

    /** @return array<string, mixed> */
    private function serializeDocument(Document $document): array
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

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
