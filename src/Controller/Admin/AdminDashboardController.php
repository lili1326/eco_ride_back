<?php

 namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return new Response('Non authentifié', 401);
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new Response('Accès refusé - rôle insuffisant', 403);
        }

        return $this->render('admin_dashboard/index.html.twig', [
            'user' => $user,
            'roles' => $user->getRoles(),
        ]);
    }
}