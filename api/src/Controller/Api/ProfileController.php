<?php

namespace App\Controller\Api;

use App\Service\ProfilePasswordResetService;
use App\Service\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/api/profile')]
final class ProfileController extends AbstractController
{
    public function __construct(
        private readonly ProfileService $profile,
        private readonly ProfilePasswordResetService $passwordReset,
    ) {}

    #[Route('', name: 'api_profile_show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        return $this->json($this->profile->show());
    }

    #[Route('', name: 'api_profile_update', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        return $this->json($this->profile->update($request));
    }

    #[Route('/password-reset-request', name: 'api_profile_password_reset_request', methods: ['POST'])]
    public function requestPasswordReset(ResetPasswordHelperInterface $resetPasswordHelper, MailerInterface $mailer): JsonResponse
    {
        return $this->json($this->passwordReset->request($resetPasswordHelper, $mailer));
    }
}
