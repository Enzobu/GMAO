<?php

namespace App\Service;

use App\Entity\User;

final readonly class UserProfileSerializer
{
    /** @return array<string, mixed> */
    public function serialize(User $user): array
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
}
