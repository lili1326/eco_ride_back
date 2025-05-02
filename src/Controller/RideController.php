<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RideController extends AbstractController
{
    #[Route('/ride', name: 'app_ride')]
    public function index(): Response
    {
        return $this->render('ride/index.html.twig', [
            'controller_name' => 'RideController',
        ]);
    }
}
