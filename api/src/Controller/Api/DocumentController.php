<?php

namespace App\Controller\Api;

use App\Entity\Document;
use App\Service\DocumentApiService;
use App\Service\DocumentFileResponseFactory;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/{parentType}/{parentId}/documents', requirements: ['parentType' => 'users|vehicles|vehicle_insurances|vehicle_inspections|maintenances|parts', 'parentId' => '\\d+'])]
final class DocumentController extends AbstractController
{
    public function __construct(
        private readonly DocumentApiService $documents,
        private readonly DocumentFileResponseFactory $fileResponses,
    ) {}

    #[Route('', name: 'api_documents_index', methods: ['GET'])]
    public function index(string $parentType, int $parentId): JsonResponse
    {
        return $this->json($this->documents->list($parentType, $parentId));
    }

    #[Route('', name: 'api_documents_create', methods: ['POST'])]
    public function create(string $parentType, int $parentId, Request $request): JsonResponse
    {
        return $this->json($this->documents->create($parentType, $parentId, $request), Response::HTTP_CREATED);
    }

    #[Route('/{publicId}', name: 'api_documents_update', methods: ['PATCH'])]
    public function update(string $parentType, int $parentId, Request $request, #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document): JsonResponse
    {
        return $this->json($this->documents->update($parentType, $parentId, $request, $document));
    }

    #[Route('/{publicId}', name: 'api_documents_delete', methods: ['DELETE'])]
    public function delete(string $parentType, int $parentId, #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document): Response
    {
        $this->documents->delete($parentType, $parentId, $document);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{publicId}/file', name: 'api_documents_file', methods: ['GET'])]
    public function showDocumentFile(string $parentType, int $parentId, #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document): BinaryFileResponse
    {
        $this->documents->assertReadable($parentType, $parentId, $document);

        return $this->fileResponses->inline($document);
    }

    #[Route('/{publicId}/download', name: 'api_documents_download', methods: ['GET'])]
    public function download(string $parentType, int $parentId, #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document): BinaryFileResponse
    {
        $this->documents->assertReadable($parentType, $parentId, $document);

        return $this->fileResponses->attachment($document);
    }
}
