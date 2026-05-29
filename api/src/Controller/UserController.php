<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
use App\Form\DocumentType;
use App\Form\UserType;
use App\Repository\DocumentRepository;
use App\Repository\UserRepository;
use Symfony\Component\Mime\Address;
use App\Service\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/user')]
final class UserController extends AbstractController
{
    use DocumentUploadTrait;

    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $this->checkAthorization();

        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findBy(['isDeleted' => false]),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ResetPasswordHelperInterface $resetPasswordHelper,
        MailerInterface $mailer,
    ): Response {
        $user = new User();
        $user->setAddress(new \App\Entity\Address());

        $this->checkAthorization(
            user: $user,
        );

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            try {
                $resetToken = $resetPasswordHelper->generateResetToken($user);

                $email = (new TemplatedEmail())
                    ->from(new Address('no-reply@enzo-palermo.com', 'Enzo PALERMO'))
                    ->to((string) $user->getEmail())
                    ->subject('Définissez votre mot de passe')
                    ->htmlTemplate('reset_password/email.html.twig')
                    ->context([
                        'resetToken' => $resetToken,
                    ]);

                $mailer->send($email);

                $this->addFlash('success', 'Utilisateur créé. Un email de définition du mot de passe a été envoyé.');
            } catch (ResetPasswordExceptionInterface $e) {
                $this->addFlash('warning', 'Utilisateur créé, mais le mail de définition du mot de passe n’a pas pu être envoyé.');
            }

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(
        User $user,
        DocumentRepository $documentRepository,
    ): Response {
        $response = $this->checkAthorization(
            user: $user,
        );

        if ($response) {
            return $response;
        }
        
        return $this->render('user/show.html.twig', [
            'user' => $user,
            'user_document' => $documentRepository->findByUser(user: $user, deleted: false),
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

            $this->addFlash('success', 'Utilisateur modifié.');

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
            $user->setIsDeleted(true);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{userId}/document/new', name: 'app_user_document_new', methods: ['GET', 'POST'])]
    public function newDocument(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'userId')] User $user,
        SluggerInterface $slugger,
    ): Response {
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        $response = $this->persistUploadedDocumentFromForm(
            $document,
            $form,
            $entityManager,
            static fn (Document $document) => $document->setUser($user),
            fn () => $this->renderDocumentForm('document/new.html.twig', $document, $form, $user, 'Utilisateur : ' . $user->displayName()),
            fn () => $this->redirectToRoute('app_user_show', ["id" => $user->getId()], Response::HTTP_SEE_OTHER),
            $slugger,
        );

        if ($response) {
            return $response;
        }

        return $this->renderDocumentForm('document/new.html.twig', $document, $form, $user, 'Assurance : ' . $user->displayName());
    }

    #[Route('/{userId}/document/{documentId}/edit', name: 'app_user_document_edit', methods: ['GET', 'POST'])]
    public function editDocument(
        Request $request,
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'userId')] User $user,
        #[MapEntity(mapping: ['documentId' => 'publicId'])] Document $document,
    ): Response {
        $response = $this->checkAthorization(
            document: $document,
            edit: true,
        );

        if ($response) {
            return $response;
        }

        $oldName = $document->getName();
        $oldDescription = $document->getDescription();

        $form = $this->createForm(DocumentType::class, $document, ['edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->flushDocumentUpdate($entityManager, $document, $oldName, $oldDescription);

            return $this->redirectToRoute('app_user_show', ["id" => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderDocumentForm('document/edit.html.twig', $document, $form, $user, 'Assurance : ' . $user->displayName());
    }

    #[Route('/{userId}/document/{documentId}', name: 'app_user_document_delete', methods: ['POST'])]
    public function deleteDocument(
        Request $request,
        DocumentManager $documentManager,
        #[MapEntity(id: 'userId')] User $user,
        #[MapEntity(mapping: ['documentId' => 'publicId'])] Document $document,
    ): Response {
        $response = $this->checkAthorization(
            document: $document,
            delete: true,
        );

        if ($response) {
            return $response;
        }

        $this->softDeleteDocumentWhenCsrfIsValid($request, $documentManager, $document);

        return $this->redirectToRoute('app_user_show', ["id" => $user->getId()], Response::HTTP_SEE_OTHER);
    }

    private function checkAthorization(
        ?User $user = null,
        ?Document $document = null,
        bool $delete = false,
        bool $edit = false,
    ): ?Response {
        # -------------------- Authization --------------------
        if ($delete || $edit) {
            $response = $this->redirectUnlessAdmin('app_user_index', [], 'Vous n\'avez pas les autorisations nécessaires pour modifier ou supprimer un élément. Veuillez contacter un administrateur');

            if ($response) {
                return $response;
            }
        }
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('warning', 'Vous avez un accès en lecture seule à la ressource demandée. ressoPour plus d\'informations, contactez un administrateururce demandée.');
            return null;
        }
        if ($user) {
            if ($user->isDeleted()) {
                $this->addFlash('danger', 'L\'utilisateur a été supprimé. ressoPour plus d\'informations, contactez un administrateururce demandée.');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        if ($document) {
            $response = $this->redirectIfDocumentIsDeleted($document, 'app_user_index');

            if ($response) {
                return $response;
            }
        }
        # -----------------------------------------------------
        return null;
    }
}
