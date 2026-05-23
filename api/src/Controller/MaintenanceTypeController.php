<?php

namespace App\Controller;

use App\Entity\MaintenanceType;
use App\Form\MaintenanceTypeFormType;
use App\Repository\MaintenanceTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/configuration/maintenance/type')]
final class MaintenanceTypeController extends AbstractController
{
    #[Route(name: 'app_configuration_maintenance_type_index', methods: ['GET'])]
    public function index(MaintenanceTypeRepository $maintenanceTypeRepository): Response
    {
        return $this->render('maintenance_type/index.html.twig', [
            'maintenance_types' => $maintenanceTypeRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_configuration_maintenance_type_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $maintenanceType = new MaintenanceType();
        $form = $this->createForm(MaintenanceTypeFormType::class, $maintenanceType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($maintenanceType);
            $entityManager->flush();

            $this->addFlash('success', 'Type d’entretien créé avec succès.');

            return $this->redirectToRoute('app_configuration_maintenance_type_index');
        }

        return $this->render('maintenance_type/new.html.twig', [
            'maintenance_type' => $maintenanceType,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_configuration_maintenance_type_show', methods: ['GET'])]
    public function show(MaintenanceType $maintenanceType): Response
    {
        return $this->render('maintenance_type/show.html.twig', [
            'maintenance_type' => $maintenanceType,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_configuration_maintenance_type_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MaintenanceType $maintenanceType, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MaintenanceTypeFormType::class, $maintenanceType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Type d’entretien mis à jour.');

            return $request->query->get('show') == 'true' ?
                $this->redirectToRoute('app_configuration_maintenance_type_show', [
                    'id' => $maintenanceType->getId(),
                ], Response::HTTP_SEE_OTHER) :
                $this->redirectToRoute('app_configuration_maintenance_type_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('maintenance_type/edit.html.twig', [
            'maintenance_type' => $maintenanceType,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_configuration_maintenance_type_delete', methods: ['POST'])]
    public function delete(Request $request, MaintenanceType $maintenanceType, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$maintenanceType->getId(), $request->getPayload()->getString('_token'))) {
            return $this->redirectToRoute('app_configuration_maintenance_type_index');
        }

        $maintenanceType->setIsDeleted(true);
        $entityManager->flush();

        $this->addFlash('success', 'Type d’entretien supprimé.');

        return $this->redirectToRoute('app_configuration_maintenance_type_index');
    }
}
