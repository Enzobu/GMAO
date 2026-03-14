<?php

namespace App\Controller;

use App\Entity\Part;
use App\Form\PartFormType;
use App\Repository\PartRepository;
use App\Repository\PartTypeRepository;
use App\Repository\VehicleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/part')]
final class PartController extends AbstractController
{
    #[Route(name: 'app_part_index', methods: ['GET'])]
    public function index(
        Request $request,
        PartRepository $partRepository,
        VehicleRepository $vehicleRepository,
        PartTypeRepository $partTypeRepository,
    ): Response {
        $response = $this->checkAthorization(
            roleAdminRequired: false,
        );

        if ($response) {
            return $response;
        }

        $vehicleId = $request->query->get('vehicle');
        $partTypeId = $request->query->get('partType');

        return $this->render('part/index.html.twig', [
            'parts' => $partRepository->findByFilters(
                $vehicleId ? (int) $vehicleId : null,
                $partTypeId ? (int) $partTypeId : null,
            ),
            'vehicles' => $vehicleRepository->findBy([], ['name' => 'ASC']),
            'partTypes' => $partTypeRepository->findBy([], ['name' => 'ASC']),
            'selectedVehicleId' => $vehicleId,
            'selectedPartTypeId' => $partTypeId,
        ]);
    }

    #[Route('/new', name: 'app_part_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $part = new Part();

        $response = $this->checkAthorization(
            part: $part,
        );

        if ($response) {
            return $response;
        }

        $form = $this->createForm(PartFormType::class, $part);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($part);
            $entityManager->flush();

            return $this->redirectToRoute('app_part_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('part/new.html.twig', [
            'part' => $part,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_part_show', methods: ['GET'])]
    public function show(Part $part): Response
    {
        $response = $this->checkAthorization(
            roleAdminRequired: false,
            part: $part,
        );

        if ($response) {
            return $response;
        }

        return $this->render('part/show.html.twig', [
            'part' => $part,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_part_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Part $part, EntityManagerInterface $entityManager): Response
    {
        $response = $this->checkAthorization(
            part: $part,
        );

        if ($response) {
            return $response;
        }

        $form = $this->createForm(PartFormType::class, $part);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Modifications enregistrées.');

            return $request->query->get('show') == 'true' ?
                $this->redirectToRoute('app_part_show', [
                    "id" => $part->getId(),
                ], Response::HTTP_SEE_OTHER) :
                $this->redirectToRoute('app_part_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('part/edit.html.twig', [
            'part' => $part,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_part_delete', methods: ['POST'])]
    public function delete(Request $request, Part $part, EntityManagerInterface $entityManager): Response
    {
        $response = $this->checkAthorization(
            part: $part,
        );

        if ($response) {
            return $response;
        }

        if ($this->isCsrfTokenValid('delete'.$part->getId(), $request->getPayload()->getString('_token'))) {
            $part->setIsDeleted(true);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_part_index', [], Response::HTTP_SEE_OTHER);
    }

    private function checkAthorization(
        bool $roleAdminRequired = true,
        ?Part $part = null,
    ): ?Response {
        # -------------------- Authization --------------------
        if ($roleAdminRequired) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('danger', 'Vous n\'avez pas les autorisations nécessaires pour accéder à la ressource demandée. Pour plus d\'information, contacter un administrateur');
                return $this->redirectToRoute('app_part_index', [], Response::HTTP_SEE_OTHER);
            }
            
        }
        if ($part) {
            if ($part->isDeleted()) {
                $this->addFlash('danger', 'La ligne de stock à été supprimée. ressoPour plus d\'informations, contactez un administrateururce demandée.');
                return $this->redirectToRoute('app_part_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('warning', 'Vous avez un accès en lecture seule à la ressource demandée. ressoPour plus d\'informations, contactez un administrateururce demandée.');
        }
        # -----------------------------------------------------
        return null;
    }
}
