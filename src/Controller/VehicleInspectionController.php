<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Form\VehicleInspectionType;
use App\Repository\VehicleInspectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vehicle')]
final class VehicleInspectionController extends AbstractController
{
    #[Route('/{vehicleId}/inspection', name: 'app_vehicle_inspection_index', methods: ['GET'])]
    public function index(
        VehicleInspectionRepository $vehicleInspectionRepository,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $inspections = $vehicleInspectionRepository->findBy([
            "vehicle" => $vehicle,
        ], ['inspectionDate' => 'DESC']);

        return $this->render('vehicle_inspection/index.html.twig', [
            'vehicle_inspections' => $inspections,
            'vehicle_id' => $vehicle->getId(),
        ]);
    }

    #[Route('/{vehicleId}/inspection/new', name: 'app_vehicle_inspection_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $vehicleInspection = new VehicleInspection();
        $form = $this->createForm(VehicleInspectionType::class, $vehicleInspection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vehicleInspection);
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicle_inspection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle_inspection/new.html.twig', [
            'vehicle_inspection' => $vehicleInspection,
            'form' => $form,
        ]);
    }

    #[Route('/{vehicleId}/inspection/{id}', name: 'app_vehicle_inspection_show', methods: ['GET'])]
    public function show(
        VehicleInspection $vehicleInspection,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        return $this->render('vehicle_inspection/show.html.twig', [
            'vehicle_inspection' => $vehicleInspection,
            'vehicle_id' => $vehicle->getId(),
        ]);
    }

    #[Route('/{vehicleId}/inspection/{id}/edit', name: 'app_vehicle_inspection_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, 
        VehicleInspection $vehicleInspection, 
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $form = $this->createForm(VehicleInspectionType::class, $vehicleInspection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicle_inspection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle_inspection/edit.html.twig', [
            'vehicle_inspection' => $vehicleInspection,
            'form' => $form,
        ]);
    }

    #[Route('/{vehicleId}/inspection/{id}', name: 'app_vehicle_inspection_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        VehicleInspection $vehicleInspection, 
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$vehicleInspection->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($vehicleInspection);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_vehicle_inspection_index', [], Response::HTTP_SEE_OTHER);
    }
}
