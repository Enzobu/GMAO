<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DocumentApiService
{
    public function __construct(
        private DocumentParentResolver $parents,
        private DocumentAccessChecker $accessChecker,
        private DocumentPayloadValidator $payloadValidator,
        private DocumentManager $documentManager,
        private DocumentSerializer $serializer,
        private EntityManagerInterface $entityManager,
    ) {}

    /** @return list<array<string, mixed>> */
    public function list(string $parentType, int $parentId): array
    {
        $this->accessChecker->denyUnlessUser();
        $parent = $this->getParentOr404($parentType, $parentId);

        return array_map(
            fn (Document $document): array => $this->serializer->serialize($document),
            $this->parents->documents($parent),
        );
    }

    /** @return array<string, mixed> */
    public function create(string $parentType, int $parentId, Request $request): array
    {
        $parent = $this->getParentOr404($parentType, $parentId);
        $this->accessChecker->denyUnlessCanManage($parent);

        $file = $this->payloadValidator->uploadedFile($request);
        $document = $this->documentManager->createDocument(
            $parent,
            $file,
            $this->payloadValidator->documentName($request, $file),
            $this->payloadValidator->requestDescription($request),
        );

        return $this->serializer->serialize($document);
    }

    /** @return array<string, mixed> */
    public function update(string $parentType, int $parentId, Request $request, Document $document): array
    {
        $parent = $this->getParentOr404($parentType, $parentId);
        $this->accessChecker->denyUnlessCanManage($parent);
        $this->accessChecker->denyUnlessDocumentBelongsToParent($document, $parent);

        $this->payloadValidator->applyMetadata($request, $document);
        $this->entityManager->flush();

        return $this->serializer->serialize($document);
    }

    public function delete(string $parentType, int $parentId, Document $document): void
    {
        $this->accessChecker->denyUnlessCanDelete();
        $parent = $this->getParentOr404($parentType, $parentId);

        if ($document->isDeleted()) {
            return;
        }

        $this->accessChecker->denyUnlessDocumentBelongsToParent($document, $parent);
        $this->documentManager->softDelete($document);
    }

    public function assertReadable(string $parentType, int $parentId, Document $document): void
    {
        $this->accessChecker->denyUnlessUser();
        $parent = $this->getParentOr404($parentType, $parentId);
        $this->accessChecker->denyUnlessDocumentBelongsToParent($document, $parent);
    }

    private function getParentOr404(string $parentType, int $parentId): User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part
    {
        $parent = $this->parents->resolve($parentType, $parentId);
        if ($parent === null || $this->parents->isDeleted($parent)) {
            throw new NotFoundHttpException('Ressource introuvable.');
        }

        return $parent;
    }
}
