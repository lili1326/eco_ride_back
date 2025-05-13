<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Participe;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};

class PassagerController extends AbstractController
{
    
#[Route('/api/passager/trajets', name: 'api_passager_trajets', methods: ['GET'])]
public function getTrajets(EntityManagerInterface $em): JsonResponse
{
    /** @var \App\Entity\User $user */
    $user = $this->getUser();

    $participations = $user->getParticipes();

    $data = [];

    foreach ($participations as $p) {
        $covoit = $p->getCovoiturage();
        $data[] = [
            'ride_id' => $covoit->getId(),
            'lieu_depart' => $covoit->getLieuDepart(),
            'lieu_arrivee' => $covoit->getLieuArrivee(),
            'date_depart' => $covoit->getDateDepart()->format('Y-m-d'),
            'statut' => $p->getStatut(),
            'conducteur' => $covoit->getConducteur()?->getPseudo()
        ];
    }

    return new JsonResponse($data);
}

#[Route('/api/passager/statut', name: 'api_update_statut', methods: ['POST'])]
public function updateStatut(Request $request, EntityManagerInterface $em): JsonResponse
{
    $user = $this->getUser();
    $data = json_decode($request->getContent(), true);

    $rideId = $data['ride_id'] ?? null;
    $newStatut = $data['statut'] ?? null;

    if (!$rideId || !$newStatut) {
        return new JsonResponse(['error' => 'Données manquantes'], 400);
    }

    $participe = $em->getRepository(Participe::class)->findOneBy([
        'utilisateur' => $user,
        'covoiturage' => $rideId
    ]);

    if (!$participe) {
        return new JsonResponse(['error' => 'Participation introuvable'], 404);
    }

    $participe->setStatut($newStatut);
    $em->flush();

    return new JsonResponse(['success' => true]);
}
}