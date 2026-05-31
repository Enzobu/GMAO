<?php

namespace App\Controller\Api;

use App\Entity\Address;
use App\Entity\Document;
use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Service\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address as EmailAddress;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/api/profile')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('', name: 'api_profile_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $user = $this->getCurrentUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], 401);
        }

        return $this->json($this->serializeProfile($user));
    }

    #[Route('', name: 'api_profile_update', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        $user = $this->getCurrentUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $validationResponse = $this->validateProfilePayload($payload);

        if ($validationResponse instanceof JsonResponse) {
            return $validationResponse;
        }

        $this->updateUserProfile($user, $payload);
        $this->entityManager->flush();

        return $this->json($this->serializeProfile($user));
    }

    #[Route('/password-reset-request', name: 'api_profile_password_reset_request', methods: ['POST'])]
    public function requestPasswordReset(
        ResetPasswordHelperInterface $resetPasswordHelper,
        MailerInterface $mailer,
    ): JsonResponse {
        $user = $this->getCurrentUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            $resetToken = $resetPasswordHelper->generateResetToken($user);

            $email = (new TemplatedEmail())
                ->from(new EmailAddress('no-reply@enzo-palermo.com', 'Enzo PALERMO'))
                ->to((string) $user->getEmail())
                ->subject('Réinitialisation de votre mot de passe')
                ->htmlTemplate('reset_password/email.html.twig')
                ->context([
                    'user' => $user,
                    'resetToken' => $resetToken,
                    'frontendResetUrl' => $this->buildFrontendResetUrl($resetToken->getToken()),
                ]);

            $mailer->send($email);
        } catch (ResetPasswordExceptionInterface) {
            return $this->json([
                'message' => 'Un email de réinitialisation a déjà été demandé récemment. Vérifiez votre boîte mail.',
            ]);
        }

        return $this->json(['message' => 'Un email de réinitialisation vous a été envoyé.']);
    }

    #[Route('/documents', name: 'api_profile_documents_index', methods: ['GET'])]
    public function documents(DocumentRepository $documentRepository): JsonResponse
    {
        $user = $this->getCurrentUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(array_map(
            fn (Document $document): array => $this->serializeDocument($document),
            $documentRepository->findByUser($user),
        ));
    }

    #[Route('/documents', name: 'api_profile_documents_create', methods: ['POST'])]
    public function createDocument(Request $request, DocumentManager $documentManager): JsonResponse
    {
        $user = $this->getCurrentUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return $this->json(['message' => 'Le fichier est obligatoire.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($file->getSize() !== false && $file->getSize() > 8 * 1024 * 1024) {
            return $this->json(['message' => 'Fichier trop volumineux. Max 8 Mo.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $name = trim((string) $request->request->get('name', ''));
        if ($name === '') {
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Document';
        }

        $document = $documentManager->createDocument(
            parent: $user,
            file: $file,
            name: $name,
            description: $this->nullableString($request->request->get('description')),
        );

        return $this->json($this->serializeDocument($document), Response::HTTP_CREATED);
    }

    #[Route('/documents/{publicId}', name: 'api_profile_documents_update', methods: ['PATCH'])]
    public function updateDocument(
        Request $request,
        #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document,
    ): JsonResponse {
        $user = $this->getCurrentUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $accessResponse = $this->denyUnlessCanManageProfileDocument($user, $document);
        if ($accessResponse instanceof JsonResponse) {
            return $accessResponse;
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['message' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            return $this->json(['message' => 'Le nom est obligatoire.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $document
            ->setName($name)
            ->setDescription($this->nullableString($payload['description'] ?? null));

        $this->entityManager->flush();

        return $this->json($this->serializeDocument($document));
    }

    #[Route('/documents/{publicId}', name: 'api_profile_documents_delete', methods: ['DELETE'])]
    public function deleteDocument(
        #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document,
        DocumentManager $documentManager,
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['message' => 'Seul un administrateur peut archiver un document.'], Response::HTTP_FORBIDDEN);
        }

        if ($document->isDeleted()) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        $documentManager->softDelete($document);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/documents/{publicId}/file', name: 'api_profile_documents_file', methods: ['GET'])]
    public function showDocumentFile(
        #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document,
        DocumentManager $documentManager,
    ): BinaryFileResponse {
        $this->denyAccessUnlessDocumentIsReadable($document);

        return $this->buildDocumentFileResponse($document, $documentManager, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/documents/{publicId}/download', name: 'api_profile_documents_download', methods: ['GET'])]
    public function download(
        #[MapEntity(mapping: ['publicId' => 'publicId'])] Document $document,
        DocumentManager $documentManager,
    ): BinaryFileResponse {
        $this->denyAccessUnlessDocumentIsReadable($document);

        return $this->buildDocumentFileResponse($document, $documentManager, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    private function getCurrentUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }

    private function denyAccessUnlessDocumentIsReadable(Document $document): void
    {
        if ($document->isDeleted()) {
            throw $this->createNotFoundException('Document introuvable.');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à ce document.');
        }

        $owner = $document->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && (!$owner instanceof User || $owner->getId() !== $user->getId())) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu’aux documents de votre profil.');
        }

    }

    private function denyUnlessCanManageProfileDocument(User $user, Document $document): ?JsonResponse
    {
        if ($document->isDeleted()) {
            return $this->json(['message' => 'Document introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $owner = $document->getUser();
        if ($this->isGranted('ROLE_ADMIN') || ($owner instanceof User && $owner->getId() === $user->getId())) {
            return null;
        }

        return $this->json(['message' => 'Vous ne pouvez modifier que vos documents.'], Response::HTTP_FORBIDDEN);
    }

    private function buildDocumentFileResponse(
        Document $document,
        DocumentManager $documentManager,
        string $disposition,
    ): BinaryFileResponse {
        if (!$documentManager->fileExists($document)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $response = $this->file(
            $documentManager->getAbsolutePath($document),
            $documentManager->getDownloadFilename($document),
            $disposition,
        );
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        if ($document->getMimeType()) {
            $response->headers->set('Content-Type', $document->getMimeType());
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeDocument(Document $document): array
    {
        return [
            'id' => $document->getId(),
            'publicId' => $document->getPublicId(),
            'name' => $document->getName(),
            'description' => $document->getDescription(),
            'originalFilename' => $document->getOriginalFilename(),
            'mimeType' => $document->getMimeType(),
            'size' => $document->getSize(),
            'extension' => $document->getExtension(),
            'createdAt' => $document->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $document->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function validateProfilePayload(mixed $payload): ?JsonResponse
    {
        $response = null;

        if (!is_array($payload)) {
            $response = $this->json(['message' => 'Invalid JSON payload'], 400);
        } elseif ($this->hasInvalidIdentityPayload($payload)) {
            $response = $this->json(['message' => 'Firstname and lastname are required'], 422);
        } elseif ($this->hasInvalidAddressPayload($payload)) {
            $response = $this->json(['message' => 'Address line1, postalCode, city and country are required'], 422);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hasInvalidIdentityPayload(array $payload): bool
    {
        $firstname = trim((string) ($payload['firstname'] ?? ''));
        $lastname = trim((string) ($payload['lastname'] ?? ''));

        return $firstname === '' || $lastname === '';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hasInvalidAddressPayload(array $payload): bool
    {
        $addressPayload = $this->getAddressPayload($payload);

        return trim((string) ($addressPayload['line1'] ?? '')) === ''
            || trim((string) ($addressPayload['postalCode'] ?? '')) === ''
            || trim((string) ($addressPayload['city'] ?? '')) === ''
            || trim((string) ($addressPayload['country'] ?? '')) === '';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function updateUserProfile(User $user, array $payload): void
    {
        $user
            ->setFirstname(trim((string) $payload['firstname']))
            ->setLastname(trim((string) $payload['lastname']));

        $addressPayload = $this->getAddressPayload($payload);
        $address = $user->getAddress() ?? new Address();

        $address
            ->setLine1(trim((string) $addressPayload['line1']))
            ->setLine2($this->nullableString($addressPayload['line2'] ?? null))
            ->setPostalCode(trim((string) $addressPayload['postalCode']))
            ->setCity(trim((string) $addressPayload['city']))
            ->setCountry(trim((string) $addressPayload['country']));

        $user->setAddress($address);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function getAddressPayload(array $payload): array
    {
        $addressPayload = $payload['address'] ?? [];

        return is_array($addressPayload) ? $addressPayload : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProfile(User $user): array
    {
        $address = $user->getAddress();

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'displayName' => $user->displayName(),
            'initials' => strtoupper(substr((string) $user->getFirstname(), 0, 1) . substr((string) $user->getLastname(), 0, 1)),
            'address' => [
                'line1' => $address?->getLine1() ?? '',
                'line2' => $address?->getLine2() ?? '',
                'postalCode' => $address?->getPostalCode() ?? '',
                'city' => $address?->getCity() ?? '',
                'country' => $address?->getCountry() ?? '',
            ],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function buildFrontendResetUrl(string $token): string
    {
        return rtrim((string) $this->getParameter('frontend_url'), '/') . '/reset-password/reset/' . rawurlencode($token);
    }
}
