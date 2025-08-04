<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class StockController extends AbstractController
{
    #[Route('/stock', name: 'app_stock')]
    public function index(
        #[CurrentUser] User $currentUser,
    ): Response
    {
        return $this->render('stock/index.html.twig', [
            'controller_name' => 'StockController',
            'currentUser' => $currentUser,
        ]);
    }
}
