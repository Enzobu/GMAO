<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class InterventionController extends AbstractController
{
    #[Route('/interventions', name: 'app_intervention')]
    public function index(
        #[CurrentUser] User $currentUser,
    ): Response
    {
        return $this->render('interventions/index.html.twig', [
            'controller_name' => 'InterventionsController',
            'currentUser' => $currentUser,
        ]);
    }
}
