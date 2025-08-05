<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UpdateProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
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
                // Vérification du mot de passe actuel
                if (!$passwordHasher->isPasswordValid($currentUser, $oldPassword)) {
                    $this->addFlash('danger', 'Votre mot de passe actuel est incorrect.');
                    return $this->redirectToRoute('app_profile');
                }
    
                // Vérification de la correspondance des nouveaux mots de passe
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

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'currentUser' => $currentUser,
            'form' => $form,
        ]);
    }
}
