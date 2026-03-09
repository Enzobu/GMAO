<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
use App\Form\DocumentType;
use App\Form\UpdateProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('profile')]
final class ProfileController extends AbstractController
{
    #[Route(name: 'app_profile')]
    public function index(
        #[CurrentUser] User $currentUser,
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $form = $this->createForm(UpdateProfileType::class, $currentUser);
        $form->get('firstname')->setData($currentUser->getFirstname());
        $form->get('lastname')->setData($currentUser->getLastname());
        $form->get('email')->setData($currentUser->getEmail());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldPassword = $form->get('old_password')->getData();
            $newPassword = $form->get('new_password')->getData();
            $newPasswordRetry = $form->get('new_password_retry')->getData();

            $email = $form->get('email')->getData();
            $firstname = $form->get('firstname')->getData();
            $lastname = $form->get('lastname')->getData();
            
            if (!(empty($oldPassword) && empty($newPassword) && empty($newPasswordRetry))) {
                if (empty($oldPassword) || empty($newPassword) || empty($newPasswordRetry)) {
                    $this->addFlash('danger', 'Veuillez remplir tous les champs pour mettre à jour votre mot de passe');
                    return $this->redirectToRoute('app_profile');
                }

                if (!$passwordHasher->isPasswordValid($currentUser, $oldPassword)) {
                    $this->addFlash('danger', 'Votre mot de passe actuel est incorrect.');
                    return $this->redirectToRoute('app_profile');
                }
    
                if ($newPassword !== $newPasswordRetry) {
                    $this->addFlash('danger', 'Les nouveaux mots de passe ne correspondent pas.');
                    return $this->redirectToRoute('app_profile');
                }
    
                $hashedPassword = $passwordHasher->hashPassword($currentUser, $newPassword);
                $currentUser->setPassword($hashedPassword);
    
                $entityManager->flush();
    
                $this->addFlash('success', 'Mot de passe mis à jour avec succès.');
            }
            
            if ((empty($email) && empty($firstname) && empty($lastname))) {
                $this->addFlash('danger', 'L\'adresse mail, le nom et le prénom ne peuvent pas être vides.');
                return $this->redirectToRoute('app_profile');
            }

            if (
                $email !== $currentUser->getEmail() || 
                $firstname !== $currentUser->getFirstname() || 
                $lastname !== $currentUser->getLastname()
            )  {
                $currentUser->setEmail($email);
                $currentUser->setFirstname($firstname);
                $currentUser->setLastname($lastname);

                $entityManager->flush();

                $this->addFlash('success', 'Données mise à jour.');
            }
                
            $this->addFlash('warning', 'Aucune données n\'a été modifiées.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form,
            'user' => $currentUser,
        ]);
    }

    #[Route('/document/new', name: 'app_profile_document_new', methods: ['GET', 'POST'])]
    public function newDocument(
        Request $request, 
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $user,
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

                return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('document/new.html.twig', [
            'document' => $document,
            'form' => $form,
            'entity' => $user,
            'subtitle' => 'Assurance : ' . $user->displayName(),
        ]);
    }

    #[Route('/document/{documentId}/edit', name: 'app_profile_document_edit', methods: ['GET', 'POST'])]
    public function editDocument(
        Request $request, 
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $user,
        #[MapEntity(id: 'documentId')] Document $document,
    ): Response {
        $oldName = $document->getName();
        $oldDescription = $document->getDescription();

        $form = $this->createForm(DocumentType::class, $document, ['edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $name = $document->getName();
            $description = $document->getDescription();

            if (($oldName != $name) || ($oldDescription != $description)) {
                $this->addFlash('success', 'Le document a bien été modifié.');
            } else {
                $this->addFlash('warning', 'Le document ne comporte aucune modification.');
            }

            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('document/edit.html.twig', [
            'document' => $document,
            'form' => $form,
            'entity' => $user,
            'subtitle' => 'Assurance : ' . $user->displayName(),
        ]);
    }

    #[Route('/document/{documentId}', name: 'app_profile_document_delete', methods: ['POST'])]
    public function deleteDocument(
        Request $request, 
        EntityManagerInterface $entityManager,
        #[CurrentUser] User $user,
        #[MapEntity(id: 'documentId')] Document $document,
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->getPayload()->getString('_token'))) {

            if (!$this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('danger', 'Vous n\'avez pas les autorisations nécessaire pour supprimer un document. Veuillez contacter un administrateur');
    
                return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($document);
            $entityManager->flush();

            $this->addFlash('success', 'Document supprimé avec succès.');
        }

        return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
    }
}
