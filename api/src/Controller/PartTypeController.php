<?php

namespace App\Controller;

use App\Entity\PartType;
use App\Form\PartTypeType;
use App\Repository\PartTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/configuration/part/type')]
final class PartTypeController extends AbstractController
{
    #[Route(name: 'app_configuration_part_type_index', methods: ['GET'])]
    public function index(PartTypeRepository $partTypeRepository): Response
    {
        return $this->render('part_type/index.html.twig', [
            'part_types' => $partTypeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_configuration_part_type_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $partType = new PartType();
        $form = $this->createForm(PartTypeType::class, $partType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($partType);
            $entityManager->flush();

            $this->addFlash('success', 'Type de pièce créé avec succès.');

            return $this->redirectToRoute('app_configuration_part_type_index');
        }

        return $this->render('part_type/new.html.twig', [
            'part_type' => $partType,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_configuration_part_type_show', methods: ['GET'])]
    public function show(PartType $partType): Response
    {
        return $this->render('part_type/show.html.twig', [
            'part_type' => $partType,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_configuration_part_type_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PartType $partType, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PartTypeType::class, $partType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Type de pièce mis à jour.');

            return $request->query->get('show') == 'true' ?
                $this->redirectToRoute('app_configuration_part_type_show', [
                    "id" => $partType->getId(),
                ], Response::HTTP_SEE_OTHER) :
                $this->redirectToRoute('app_configuration_part_type_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('part_type/edit.html.twig', [
            'part_type' => $partType,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_configuration_part_type_delete', methods: ['POST'])]
    public function delete(Request $request, PartType $partType, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$partType->getId(), $request->getPayload()->getString('_token'))) {
            return $this->redirectToRoute('app_configuration_part_type_index');
        }

        if (!$partType->getParts()->isEmpty()) {
            $this->addFlash('danger', 'Impossible de supprimer ce type : des pièces utilisent encore ce type.');

            return $this->redirectToRoute('app_configuration_part_type_index');
        }

        $partType->setIsDeleted(true);
        $entityManager->flush();

        $this->addFlash('success', 'Type de pièce supprimé.');

        return $this->redirectToRoute('app_configuration_part_type_index');
    }
}
