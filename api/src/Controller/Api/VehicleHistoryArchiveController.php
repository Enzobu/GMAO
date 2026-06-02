<?php

namespace App\Controller\Api;

use App\Repository\VehicleRepository;
use App\Service\DocumentAccessChecker;
use App\Service\VehicleHistoryArchiveBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class VehicleHistoryArchiveController extends AbstractController
{
    public function __construct(
        private readonly VehicleRepository $vehicles,
        private readonly DocumentAccessChecker $accessChecker,
        private readonly VehicleHistoryArchiveBuilder $archiveBuilder,
    ) {}

    #[Route('/api/vehicles/{id}/history/archive', name: 'api_vehicle_history_archive', methods: ['GET'])]
    public function download(int $id): BinaryFileResponse
    {
        $vehicle = $this->vehicles->find($id);

        if (!$vehicle || $vehicle->isDeleted()) {
            throw new NotFoundHttpException('Véhicule introuvable.');
        }

        $this->accessChecker->denyUnlessCanManage($vehicle);
        $archive = $this->archiveBuilder->build($vehicle);
        $response = new BinaryFileResponse($archive->path);

        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $archive->filename,
        );
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
