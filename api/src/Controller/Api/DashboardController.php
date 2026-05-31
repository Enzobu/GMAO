<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\DashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    public function __construct(private readonly DashboardService $dashboard) {}

    #[Route('/api/dashboard', name: 'api_dashboard', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['message' => 'Unauthenticated'], 401);
        }

        return $this->json($this->dashboard->dashboardFor($user));
    }
}
