<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Service\VehicleManager;
use Symfony\Component\HttpFoundation\Response;

trait VehicleEventAuthorizationTrait
{
    /**
     * @param array<string, mixed> $params
     */
    protected function checkVehicleEventAuthorization(
        VehicleManager $vehicleManager,
        User $currentUser,
        Vehicle $vehicle,
        object $event,
        string $eventIndexRoute,
        string $eventDeletedMessage,
        ?Document $document = null,
        array $params = [],
        bool $delete = false,
        bool $update = false,
        bool $new = false,
    ): ?Response {
        if (!$vehicleManager->isAuthorized($currentUser, $vehicle)) {
            $update
                ? $this->addFlash('danger', 'Vous ne pouvez pas modifier la ressource demandée. ressoPour plus d\'informations, contactez un administrateururce demandée.')
                : $this->addFlash('warning', 'Vous avez un accès en lecture seule à la ressource demandée. ressoPour plus d\'informations, contactez un administrateururce demandée.');

            if ($update) {
                return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        if ($vehicle->isDeleted()) {
            $this->addFlash('danger', 'Le véhicule a été supprimé. ressoPour plus d\'informations, contactez un administrateururce demandée.');

            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($event->isDeleted()) {
            $this->addFlash('warning', $eventDeletedMessage);

            return $this->redirectToRoute($eventIndexRoute, $params, Response::HTTP_SEE_OTHER);
        }

        if ($new && !$vehicleManager->isAuthorized($currentUser, $vehicle)) {
            $this->addFlash('danger', 'Vous ne pouvez pas ajouter un contrôle technique pour ce vehicule. ressoPour plus d\'informations, contactez un administrateururce demandée.');

            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($document) {
            $response = $this->checkVehicleEventDocumentAuthorization($vehicleManager, $currentUser, $vehicle, $document, $eventIndexRoute, $params);

            if ($response) {
                return $response;
            }
        }

        if ($delete && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', 'Vous n\'avez pas les autorisations nécessaires pour supprimer un document. Veuillez contacter un administrateur');

            return $this->redirectToRoute('app_vehicle_show', ['id' => $params['vehicleId']], Response::HTTP_SEE_OTHER);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function checkVehicleEventDocumentAuthorization(
        VehicleManager $vehicleManager,
        User $currentUser,
        Vehicle $vehicle,
        Document $document,
        string $eventIndexRoute,
        array $params,
    ): ?Response {
        if (!$vehicleManager->isAuthorized($currentUser, $vehicle)) {
            $this->addFlash('danger', 'Vous ne pouvez pas ajouter un document sur la ressource demandée. ressoPour plus d\'informations, contactez un administrateururce demandée.');

            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($document->isDeleted()) {
            $this->addFlash('danger', 'Le document a été supprimé. ressoPour plus d\'informations, contactez un administrateururce demandée.');

            return $this->redirectToRoute($eventIndexRoute, $params, Response::HTTP_SEE_OTHER);
        }

        return null;
    }
}
