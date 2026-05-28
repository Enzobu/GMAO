<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
use App\Form\DocumentType;
use App\Form\UpdateProfileType;
use App\Repository\DocumentRepository;
use App\Service\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('profile')]
final class ProfileController extends AbstractController
{
    use DocumentUploadTrait;

    #[Route(name: 'app_profile')]
    public function index(
        #[CurrentUser] User $currentUser,
        Request $request,
        EntityManagerInterface $entityManager,
        DocumentRepository $documentRepository,
    ): Response {
        if (!$currentUser->getAddress()) {
            $currentUser->setAddress(new \App\Entity\Address());
        }

        $userSnapshot = [
            'email' => $currentUser->getEmail(),
            'firstname' => $currentUser->getFirstname(),
            'lastname' => $currentUser->getLastname(),
        ];

        $addressSnapshot = [
            'line1' => $currentUser->getAddress()?->getLine1(),
            'line2' => $currentUser->getAddress()?->getLine2(),
            'postalCode' => $currentUser->getAddress()?->getPostalCode(),
            'city' => $currentUser->getAddress()?->getCity(),
            'country' => $currentUser->getAddress()?->getCountry(),
        ];

        $form = $this->createForm(UpdateProfileType::class, $currentUser);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userIsUpdated =
                $userSnapshot['email'] !== $currentUser->getEmail() ||
                $userSnapshot['firstname'] !== $currentUser->getFirstname() ||
                $userSnapshot['lastname'] !== $currentUser->getLastname();

            $addressIsUpdated =
                $addressSnapshot['line1'] !== $currentUser->getAddress()?->getLine1() ||
                $addressSnapshot['line2'] !== $currentUser->getAddress()?->getLine2() ||
                $addressSnapshot['postalCode'] !== $currentUser->getAddress()?->getPostalCode() ||
                $addressSnapshot['city'] !== $currentUser->getAddress()?->getCity() ||
                $addressSnapshot['country'] !== $currentUser->getAddress()?->getCountry();

            if ($userIsUpdated || $addressIsUpdated) {
                $entityManager->flush();
                $this->addFlash('success', 'Modifications enregistrées.');
            } else {
                $this->addFlash('warning', 'Aucune modification à enregistrer.');
            }

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/index.html.twig', [
            'form' => $form,
            'user' => $currentUser,
            'user_document' => $documentRepository->findByUser(user: $currentUser, deleted: false),
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

        $response = $this->persistUploadedDocumentFromForm(
            $document,
            $form,
            $entityManager,
            static fn (Document $document) => $document->setUser($user),
            fn () => $this->render('document/new.html.twig', [
                'document' => $document,
                'form' => $form,
                'entity' => $user,
                'subtitle' => 'Utilisateur : ' . $user->displayName(),
            ]),
            fn () => $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER),
            $slugger,
        );

        if ($response) {
            return $response;
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
        #[MapEntity(mapping: ['documentId' => 'publicId'])] Document $document,
    ): Response {
        $response = $this->checkAthorization(
            document: $document,
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
        DocumentManager $documentManager,
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

        return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
    }

    private function checkAthorization(
        ?Document $document = null,
        ?Array $params = [],
        bool $delete = false,
    ): ?Response {
        # -------------------- Authization --------------------
        if ($document) {
            $response = $this->redirectIfDocumentIsDeleted($document, 'app_vehicle_index', $params ?? []);

            if ($response) {
                return $response;
            }
        }
        if ($delete) {
            $response = $this->redirectUnlessAdmin('app_vehicle_show', $params ?? [], 'Vous n\'avez pas les autorisations nécessaires pour supprimer un document. Veuillez contacter un administrateur');

            if ($response) {
                return $response;
            }
        }
        # -----------------------------------------------------
        return null;
    }
}
