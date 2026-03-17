<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Service\DocumentManager;
use App\Service\VehicleManager;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/document')]
final class DocumentController extends AbstractController
{
    #[Route('/{publicId}', name: 'app_document_show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document, 
        VehicleManager $vehicleManager,
        DocumentManager $documentManager,
    ): BinaryFileResponse {
        $this->denyAccessUnlessDocumentIsViewable($document, $vehicleManager);

        return $this->buildFileResponse(
            document: $document,
            documentManager: $documentManager,
            disposition: ResponseHeaderBag::DISPOSITION_INLINE,
        );
    }

    #[Route('/{publicId}/download', name: 'app_document_download', methods: ['GET'])]
    public function download(
        #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document, 
        VehicleManager $vehicleManager,
        DocumentManager $documentManager,
    ): BinaryFileResponse {
        $this->denyAccessUnlessDocumentIsViewable($document, $vehicleManager);

        return $this->buildFileResponse(
            document: $document,
            documentManager: $documentManager,
            disposition: ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        );
    }

    private function buildFileResponse(
        Document $document,
        DocumentManager $documentManager,
        string $disposition,
    ): BinaryFileResponse {
        if (!$documentManager->fileExists($document)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $filePath = $documentManager->getAbsolutePath($document);

        $response = $this->file(
            $filePath,
            $documentManager->getDownloadFilename($document),
            $disposition,
        );

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        if ($document->getMimeType()) {
            $response->headers->set('Content-Type', $document->getMimeType());
        }

        return $response;
    }

    private function denyAccessUnlessDocumentIsViewable(
        Document $document,
        VehicleManager $vehicleManager,
    ): void {
        if ($document->isDeleted()) {
            throw $this->createNotFoundException('Document introuvable.');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à ce document.');
        }

        if ($this->isGranted('ROLE_USER')) {
            return;
        }

        $vehicle = $this->resolveLinkedVehicle($document);

        if ($vehicle instanceof Vehicle) {
            if ($vehicle->isDeleted()) {
                throw $this->createNotFoundException('Document introuvable.');
            }

            if ($vehicleManager->isAuthorized($user, $vehicle)) {
                return;
            }
        }

        $owner = $document->getUser();

        if ($owner instanceof User && $owner->getId() === $user->getId()) {
            return;
        }

        throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à ce document.');
    }

    private function resolveLinkedVehicle(Document $document): ?Vehicle
    {
        if ($document->getVehicle() instanceof Vehicle) {
            return $document->getVehicle();
        }

        if (
            null !== $document->getVehicleInsurance()
            && method_exists($document->getVehicleInsurance(), 'getVehicle')
        ) {
            $vehicle = $document->getVehicleInsurance()->getVehicle();

            if ($vehicle instanceof Vehicle) {
                return $vehicle;
            }
        }

        if (
            null !== $document->getVehicleInspection()
            && method_exists($document->getVehicleInspection(), 'getVehicle')
        ) {
            $vehicle = $document->getVehicleInspection()->getVehicle();

            if ($vehicle instanceof Vehicle) {
                return $vehicle;
            }
        }

        return null;
    }
}
