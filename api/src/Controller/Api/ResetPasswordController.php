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

        if (!is_array($payload)) {
            return $this->json(['message' => 'Invalid JSON payload'], 400);
        }

        $plainPassword = (string) ($payload['password'] ?? '');

        if (strlen($plainPassword) < 8) {
            return $this->json(['message' => 'Le mot de passe doit contenir au moins 8 caractères.'], 422);
        }

        try {
            $user = $resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            return $this->json(['message' => 'Le lien de réinitialisation est invalide ou expiré.'], 400);
        }

        if (!$user instanceof User) {
            return $this->json(['message' => 'Utilisateur introuvable.'], 404);
        }

        $resetPasswordHelper->removeResetRequest($token);

        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $this->entityManager->flush();

        return $this->json(['message' => 'Votre mot de passe a été réinitialisé.']);
    }
}
