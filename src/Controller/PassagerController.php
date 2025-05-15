<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Participe;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Serializer\SerializerInterface;



class PassagerController extends AbstractController
{
 #[Route('/api/passager/trajets', name: 'api_passager_trajets', methods: ['GET'])]
public function getTrajets(EntityManagerInterface $em): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
    }

    $participations = $em->getRepository(Participe::class)->findBy(['utilisateur' => $user]);
    $reviewRepo = $em->getRepository(\App\Entity\Review::class);

    $data = [];

    foreach ($participations as $p) {
        $covoit = $p->getCovoiturage();

        $existing = $reviewRepo->findOneBy([
            'auteur' => $user,
            'covoiturage' => $covoit,
        ]);

        $data[] = [
            'id' => $covoit->getId(),
            'lieu_depart' => $covoit->getLieuDepart(),
            'lieu_arrivee' => $covoit->getLieuArrivee(),
            'date_depart' => $covoit->getDateDepart()->format('Y-m-d'),
            'statut' => $p->getStatut(),
            'conducteur' => [
                'id' => $covoit->getConducteur()?->getId(),
                'firstName' => $covoit->getConducteur()?->getFirstName(),
            ],
            'avisDejaLaisse' => $existing !== null
        ];
    }

    return new JsonResponse($data, Response::HTTP_OK);
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