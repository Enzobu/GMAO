<?php

namespace App\Controller\Api;

use App\Service\PasswordResetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/api/reset-password')]
final class ResetPasswordController extends AbstractController
{
    public function __construct(private readonly PasswordResetService $passwordReset) {}

    #[Route('/reset/{token}', name: 'api_reset_password', methods: ['POST'])]
    public function reset(
        string $token,
        Request $request,
        ResetPasswordHelperInterface $resetPasswordHelper,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        return $this->json($this->passwordReset->reset($token, $request, $resetPasswordHelper, $passwordHasher));
    }
}
