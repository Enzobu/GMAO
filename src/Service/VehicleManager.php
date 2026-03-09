<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Vehicle;
use Symfony\Bundle\SecurityBundle\Security;

class VehicleManager
{
    public function __construct(
        private Security $security
    ) {}
    
    public function isAuthorized(User $user, Vehicle $vehicle): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($vehicle->getUser() === $user) {
            return true;
        }

        return false;
    }
}
