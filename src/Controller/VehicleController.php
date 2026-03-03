<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleMaintenance;
use App\Form\VehicleType;
use App\Repository\VehicleInspectionRepository;
use App\Repository\VehicleInsuranceRepository;
use App\Repository\VehicleMaintenanceRepository;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/vehicle')]
final class VehicleController extends AbstractController
{
    #[Route(name: 'app_vehicle_index', methods: ['GET'])]
    public function index(
        VehicleRepository $vehicleRepository,
        #[CurrentUser] User $currentUser,
    ): Response {
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $vehicles = $isAdmin ? 
            $vehicleRepository->findAll() :
            $vehicleRepository->findByUser(user: $currentUser);

        return $this->render('vehicle/index.html.twig', [
            'vehicles' => $vehicles,
        ]);
    }

    #[Route('/new', name: 'app_vehicle_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $currentUser,
    ): Response {
        $vehicle = (new Vehicle())->setUser($currentUser);

        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vehicle);
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle/new.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_vehicle_show', methods: ['GET'])]
    public function show(
        Vehicle $vehicle,
        VehicleInsuranceRepository $vehicleInsuranceRepository,
        VehicleInspectionRepository $vehicleInspectionRepository,
        VehicleMaintenanceRepository $vehicleMaintenanceRepository,
    ): Response
    {
        $insurance = $vehicleInsuranceRepository->findBy([
            "vehicle" => $vehicle,
        ], ['startDate' => 'DESC']);

        $inspection = $vehicleInspectionRepository->findBy([        
            "vehicle" => $vehicle,
        ], ['inspectionDate' => 'DESC']);
        $maintenance = $vehicleMaintenanceRepository->findBy([
            "vehicle" => $vehicle,
        ]);

        return $this->render('vehicle/show.html.twig', [
            'vehicle' => $vehicle,
            'insurance' => $insurance[0],
            'inspection' => $inspection[0],
            'maintenance' => $maintenance,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_vehicle_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(VehicleType::class, $vehicle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $request->query->get('show') == 'true' ?
                $this->redirectToRoute('app_vehicle_show', ["id" => $vehicle->getId()], Response::HTTP_SEE_OTHER) :
                $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle/edit.html.twig', [
            'vehicle' => $vehicle,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_vehicle_delete', methods: ['POST'])]
    public function delete(Request $request, Vehicle $vehicle, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vehicle->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($vehicle);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_vehicle_index', [], Response::HTTP_SEE_OTHER);
    }
}
