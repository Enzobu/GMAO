<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class VehiculeController extends AbstractController
{
    #[Route('/vehicule', name: 'app_vehicule')]
    public function index(
        #[CurrentUser] User $currentUser,
    ): Response
    {
        return $this->render('vehicule/index.html.twig', [
            'controller_name' => 'VehiculeController',
            'currentUser' => $currentUser,
        ]);
    }
}
