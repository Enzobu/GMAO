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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $response = $this->checkAthorization();

        if ($response) {
            return $response;
        }

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

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form->get('file')->getData();

            if ($uploadedFile !== null) {
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $extension = $uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension() ?: 'bin';

                $mimeType = $uploadedFile->getMimeType();
                $size = $uploadedFile->getSize();

                $storedFilename = sprintf('%s-%s.%s', $safeFilename, uniqid(), $extension);

                try {
                    $uploadedFile->move(
                        $this->getParameter('documents_directory'),
                        $storedFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Le fichier n’a pas pu être envoyé.');

                    return $this->render('document/new.html.twig', [
                        'document' => $document,
                        'form' => $form,
                        'entity' => $user,
                        'subtitle' => 'Utilisateur : ' . $user->displayName(),
                    ]);
                }

                $document
                    ->setUser($user)
                    ->setOriginalFilename($uploadedFile->getClientOriginalName())
                    ->setStoredFilename($storedFilename)
                    ->setMimeType($mimeType)
                    ->setSize($size)
                    ->setExtension($extension)
                ;

                if (!$document->getName()) {
                    $document->setName($originalFilename);
                }

                $entityManager->persist($document);
                $entityManager->flush();

                $this->addFlash('success', 'Le document a bien été ajouté.');

                return $this->redirectToRoute('app_user_show', ["id" => $user->getId()], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('document/new.html.twig', [
            'document' => $document,
            'form' => $form,
            'entity' => $user,
            'subtitle' => 'Assurance : ' . $user->displayName(),
        ]);
    }

    #[Route('/{userId}/document/{documentId}/edit', name: 'app_user_document_edit', methods: ['GET', 'POST'])]
    public function editDocument(
        Request $request, 
        EntityManagerInterface $entityManager,
        #[MapEntity(id: 'userId')] User $user,
        #[MapEntity(id: 'documentId')] Document $document,
    ): Response {
        $this->checkAthorization(
            document: $document,
        );

        $oldName = $document->getName();
        $oldDescription = $document->getDescription();

        $form = $this->createForm(DocumentType::class, $document, ['edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $name = $document->getName();
            $description = $document->getDescription();

            ($oldName != $name) || ($oldDescription != $description) ?
            $this->addFlash('success', 'Le document a bien été modifié.') :
            $this->addFlash('warning', 'Le document ne comporte aucune modification.');

            return $this->redirectToRoute('app_user_show', ["id" => $user->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('document/edit.html.twig', [
            'document' => $document,
            'form' => $form,
            'entity' => $user,
            'subtitle' => 'Assurance : ' . $user->displayName(),
        ]);
    }

    #[Route('/{userId}/document/{documentId}', name: 'app_user_document_delete', methods: ['POST'])]
    public function deleteDocument(
        Request $request, 
        DocumentManager $documentManager,
        #[MapEntity(id: 'userId')] User $user,
        #[MapEntity(id: 'documentId')] Document $document,
    ): Response {
        $this->checkAthorization(
            document: $document,
            delete: true,
        );

        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->getPayload()->getString('_token'))) {
            $documentManager->softDelete($document);

            $this->addFlash('success', 'Document supprimé avec succès.');
        }

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
            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('danger', 'Vous n\'avez pas les autorisations nécessaires pour modifier ou supprimer un élément. Veuillez contacter un administrateur');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
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
            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('danger', 'Vous ne pouvez pas ajouter ou modifier un document sur la ressource demandée. ressoPour plus d\'informations, contactez un administrateururce demandée.');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            }
            if ($document->isDeleted()) {
                $this->addFlash('danger', 'Le document a été supprimé. ressoPour plus d\'informations, contactez un administrateururce demandée.');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            }
        }
        # -----------------------------------------------------
        return null;
    }
}
