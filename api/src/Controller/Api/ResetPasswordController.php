<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/api/reset-password')]
final class ResetPasswordController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route('/reset/{token}', name: 'api_reset_password', methods: ['POST'])]
    public function reset(
        string $token,
        Request $request,
        ResetPasswordHelperInterface $resetPasswordHelper,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        $payloadErrorResponse = $this->validatePasswordPayload($payload);

        if ($payloadErrorResponse instanceof JsonResponse) {
            return $payloadErrorResponse;
        }

        $userResult = $this->validateTokenAndFetchUser($token, $resetPasswordHelper);

        if ($userResult instanceof JsonResponse) {
            return $userResult;
        }

        $this->resetUserPassword(
            $userResult,
            (string) $payload['password'],
            $token,
            $resetPasswordHelper,
            $passwordHasher,
        );

        return $this->json(['message' => 'Votre mot de passe a été réinitialisé.']);
    }

    private function validatePasswordPayload(mixed $payload): ?JsonResponse
    {
        if (!is_array($payload)) {
            return $this->json(['message' => 'Invalid JSON payload'], 400);
        }

        if (strlen((string) ($payload['password'] ?? '')) < 8) {
            return $this->json(['message' => 'Le mot de passe doit contenir au moins 8 caractères.'], 422);
        }

        return null;
    }

    private function validateTokenAndFetchUser(
        string $token,
        ResetPasswordHelperInterface $resetPasswordHelper,
    ): User|JsonResponse {
        try {
            $user = $resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            return $this->json(['message' => 'Le lien de réinitialisation est invalide ou expiré.'], 400);
        }

        if (!$user instanceof User) {
            return $this->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        return $user;
    }

    private function resetUserPassword(
        User $user,
        string $plainPassword,
        string $token,
        ResetPasswordHelperInterface $resetPasswordHelper,
        UserPasswordHasherInterface $passwordHasher,
    ): void {
        $resetPasswordHelper->removeResetRequest($token);

        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->flush();
    }
}