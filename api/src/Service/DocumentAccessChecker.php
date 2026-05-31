<?php

namespace App\Service;

use App\Entity\Document;
use App\Entity\Maintenance;
use App\Entity\Part;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Entity\VehicleInspection;
use App\Entity\VehicleInsurance;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final readonly class DocumentAccessChecker
{
    public function __construct(
        private DocumentParentResolver $parents,
        private TokenStorageInterface $tokenStorage,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    public function currentUser(): User
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (!$user instanceof User) {
            throw new HttpException(401, 'Unauthenticated');
        }

        return $user;
    }

    public function denyUnlessUser(): void
    {
        if (!$this->authorizationChecker->isGranted('ROLE_USER')) {
            throw new HttpException(401, 'Unauthenticated');
        }
    }

    public function denyUnlessCanManage(User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part $parent): void
    {
        $currentUser = $this->currentUser();
        $vehicle = $this->parents->owningVehicle($parent);

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($parent instanceof User && $parent->getId() === $currentUser->getId()) {
            return;
        }

        if ($vehicle !== null && $vehicle->getUser()?->getId() === $currentUser->getId()) {
            return;
        }

        throw new AccessDeniedHttpException('Vous ne pouvez modifier que vos documents.');
    }

    public function denyUnlessCanDelete(): void
    {
        if (!$this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Seul un administrateur peut archiver un document.');
        }
    }

    public function denyUnlessDocumentBelongsToParent(Document $document, User|Vehicle|VehicleInsurance|VehicleInspection|Maintenance|Part $parent): void
    {
        if ($document->isDeleted() || !$this->parents->belongsToParent($document, $parent)) {
            throw new NotFoundHttpException('Document introuvable.');
        }
    }
}
