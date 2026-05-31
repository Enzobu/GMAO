<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final readonly class PasswordResetService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    /** @return array{message:string} */
    public function reset(string $token, Request $request, ResetPasswordHelperInterface $resetPasswordHelper, UserPasswordHasherInterface $passwordHasher): array
    {
        $plainPassword = $this->plainPassword($request);
        $user = $this->validateTokenAndFetchUser($token, $resetPasswordHelper);

        $resetPasswordHelper->removeResetRequest($token);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $this->entityManager->flush();

        return ['message' => 'Votre mot de passe a été réinitialisé.'];
    }

    private function plainPassword(Request $request): string
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid JSON payload');
        }

        $password = (string) ($payload['password'] ?? '');
        if (strlen($password) < 8) {
            throw new UnprocessableEntityHttpException('Le mot de passe doit contenir au moins 8 caractères.');
        }

        return $password;
    }

    private function validateTokenAndFetchUser(string $token, ResetPasswordHelperInterface $resetPasswordHelper): User
    {
        try {
            $user = $resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            throw new BadRequestHttpException('Le lien de réinitialisation est invalide ou expiré.');
        }

        if (!$user instanceof User) {
            throw new NotFoundHttpException('Utilisateur introuvable.');
        }

        return $user;
    }
}
