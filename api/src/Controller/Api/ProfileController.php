<?php

namespace App\Controller\Api;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

        if (!is_array($payload)) {
            return $this->json(['message' => 'Invalid JSON payload'], 400);
        }

        $firstname = trim((string) ($payload['firstname'] ?? ''));
        $lastname = trim((string) ($payload['lastname'] ?? ''));

        if ($firstname === '' || $lastname === '') {
            return $this->json(['message' => 'Firstname and lastname are required'], 422);
        }

        $user
            ->setFirstname($firstname)
            ->setLastname($lastname);

        $addressPayload = is_array($payload['address'] ?? null) ? $payload['address'] : [];
        $address = $user->getAddress() ?? new Address();

        $address
            ->setLine1(trim((string) ($addressPayload['line1'] ?? '')))
            ->setLine2($this->nullableString($addressPayload['line2'] ?? null))
            ->setPostalCode(trim((string) ($addressPayload['postalCode'] ?? '')))
            ->setCity(trim((string) ($addressPayload['city'] ?? '')))
            ->setCountry(trim((string) ($addressPayload['country'] ?? '')));

        if ($address->getLine1() === '' || $address->getPostalCode() === '' || $address->getCity() === '' || $address->getCountry() === '') {
            return $this->json(['message' => 'Address line1, postalCode, city and country are required'], 422);
        }

        $user->setAddress($address);
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

    private function getCurrentUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
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
