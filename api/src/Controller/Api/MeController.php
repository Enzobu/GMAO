<?php

namespace App\Controller\Api;

use App\Service\CurrentUserProvider;
use App\Service\CurrentUserSerializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class MeController extends AbstractController
{
    public function __construct(
        private readonly CurrentUserProvider $currentUser,
        private readonly CurrentUserSerializer $serializer,
    ) {}

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json($this->serializer->serialize($this->currentUser->user()));
    }
}
