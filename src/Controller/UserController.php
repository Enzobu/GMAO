<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $response = $this->checkAthorization();

        if ($response) {
            return $response;
        }

        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $user->setAddress(new \App\Entity\Address());

        $response = $this->checkAthorization(
            user: $user,
        );

        if ($response) {
            return $response;
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        $response = $this->checkAthorization(
            user: $user,
        );

        if ($response) {
            return $response;
        }

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $response = $this->checkAthorization(
            user: $user,
            edit: true,
        );

        if ($response) {
            return $response;
        }

        if (!$user->getAddress()) {
            $user->setAddress(new \App\Entity\Address());
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $response = $this->checkAthorization(
            user: $user,
            delete: true,
        );

        if ($response) {
            return $response;
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    private function checkAthorization(
        ?User $user = null,
        ?Document $document = null,
        bool $delete = false,
        bool $edit = false,
    ): ?Response {
        # -------------------- Authization --------------------
        if ($delete || $edit) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('danger', 'Vous n\'avez pas les autorisations nécessaire pour modifier ou supprimer un élément. Veuillez contacter un administrateur');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('warning', 'Vous avez un accès en lecture seule à la ressource demandé. Pour plus d\'information, contactez un administrateur');
            return null;
        }
        if ($user) {
            if ($user->isDeleted()) {
                $this->addFlash('danger', 'L\'utilisateur a été supprimé. Pour plus d\'information, contactez un administrateur');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        if ($document) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('danger', 'Vous ne pouvez pas ajouter ou modifier un document sur la ressource demandé. Pour plus d\'information, contactez un administrateur');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            }
            if ($document->isDeleted()) {
                $this->addFlash('danger', 'Le document a été supprimé. Pour plus d\'information, contactez un administrateur');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        # -----------------------------------------------------
        return null;
    }
}
