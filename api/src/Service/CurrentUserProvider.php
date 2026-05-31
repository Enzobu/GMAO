<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class CurrentUserProvider
{
    public function __construct(private TokenStorageInterface $tokenStorage) {}

    public function user(): User
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof User) {
            throw new HttpException(401, 'Unauthenticated');
        }

        return $user;
    }
}
