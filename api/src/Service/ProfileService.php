<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ProfileService
{
    public function __construct(
        private CurrentUserProvider $currentUser,
        private ProfilePayloadValidator $payloadValidator,
        private UserProfileSerializer $serializer,
        private EntityManagerInterface $entityManager,
    ) {}

    /** @return array<string, mixed> */
    public function show(): array
    {
        return $this->serializer->serialize($this->currentUser->user());
    }

    /** @return array<string, mixed> */
    public function update(Request $request): array
    {
        $user = $this->currentUser->user();
        $payload = $this->payloadValidator->payload($request);

        $this->updateUserProfile($user, $payload);
        $this->entityManager->flush();

        return $this->serializer->serialize($user);
    }

    /** @param array<string, mixed> $payload */
    private function updateUserProfile(User $user, array $payload): void
    {
        $user
            ->setFirstname(trim((string) $payload['firstname']))
            ->setLastname(trim((string) $payload['lastname']));

        $addressPayload = $this->payloadValidator->addressPayload($payload);
        $address = $user->getAddress() ?? new Address();

        $address
            ->setLine1(trim((string) $addressPayload['line1']))
            ->setLine2($this->payloadValidator->nullableString($addressPayload['line2'] ?? null))
            ->setPostalCode(trim((string) $addressPayload['postalCode']))
            ->setCity(trim((string) $addressPayload['city']))
            ->setCountry(trim((string) $addressPayload['country']));

        $user->setAddress($address);
    }
}
