<?php

namespace App\Controller;

use App\Entity\Vehicle;
use App\Entity\VehicleInsurance;
use App\Form\VehicleInsuranceType;
use App\Repository\VehicleInsuranceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vehicle')]
final class VehicleInsuranceController extends AbstractController
{
    #[Route('/{vehicleId}/insurance', name: 'app_vehicle_insurance_index', methods: ['GET'])]
    public function index(
        VehicleInsuranceRepository $vehicleInsuranceRepository,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $insurances = $vehicleInsuranceRepository->findBy([
            "vehicle" => $vehicle,
        ], ['startDate' => 'DESC']);

        return $this->render('vehicle_insurance/index.html.twig', [
            'vehicle_insurances' => $insurances,
            'vehicle_id' => $vehicle->getId(),
        ]);
    }

    #[Route('/{vehicleId}/insurance/new', name: 'app_vehicle_insurance_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $vehicleInsurance = new VehicleInsurance();
        $form = $this->createForm(VehicleInsuranceType::class, $vehicleInsurance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vehicleInsurance);
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicle_insurance_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle_insurance/new.html.twig', [
            'vehicle_insurance' => $vehicleInsurance,
            'form' => $form,
        ]);
    }

    #[Route('/{vehicleId}/insurance/{id}', name: 'app_vehicle_insurance_show', methods: ['GET'])]
    public function show(
        VehicleInsurance $vehicleInsurance,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        return $this->render('vehicle_insurance/show.html.twig', [
            'vehicle_insurance' => $vehicleInsurance,
            'vehicle_id' => $vehicle->getId(),
        ]);
    }

    #[Route('/{vehicleId}/insurance/{id}/edit', name: 'app_vehicle_insurance_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        VehicleInsurance $vehicleInsurance, 
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        $form = $this->createForm(VehicleInsuranceType::class, $vehicleInsurance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_vehicle_insurance_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vehicle_insurance/edit.html.twig', [
            'vehicle_insurance' => $vehicleInsurance,
            'form' => $form,
        ]);
    }

    #[Route('/{vehicleId}/insurance/{id}', name: 'app_vehicle_insurance_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        VehicleInsurance $vehicleInsurance,
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'vehicleId')] Vehicle $vehicle,
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$vehicleInsurance->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($vehicleInsurance);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_vehicle_insurance_index', [], Response::HTTP_SEE_OTHER);
    }
}
