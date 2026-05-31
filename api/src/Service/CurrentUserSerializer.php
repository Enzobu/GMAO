<?php

namespace App\Service;

use App\Entity\User;

final readonly class CurrentUserSerializer
{
    /** @return array<string, mixed> */
    public function serialize(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'displayName' => $user->displayName(),
            'initials' => strtoupper(substr($user->getFirstname(), 0, 1) . substr($user->getLastname(), 0, 1)),
        ];
    }
}
