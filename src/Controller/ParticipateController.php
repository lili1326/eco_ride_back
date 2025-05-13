<?php

namespace App\Controller;

use App\Entity\Ride;
use App\Entity\Participe;
use App\Service\RideHistoryLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, JsonResponse};
use Symfony\Component\Routing\Annotation\Route;

final class ParticipateController extends AbstractController
{
    private RideHistoryLogger $rideHistoryLogger;

    public function __construct(RideHistoryLogger $rideHistoryLogger)
    {
        $this->rideHistoryLogger = $rideHistoryLogger;
    }

    #[Route('/api/participate', name: 'api_participate', methods: ['POST'])]
    public function apiParticipate(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $rideId = $data['ride_id'] ?? null;

        if (!$rideId) {
            return new JsonResponse(['error' => 'ride_id manquant'], 400);
        }

        /** @var Ride|null $ride */
        $ride = $em->getRepository(Ride::class)->find($rideId);
        if (!$ride) {
            return new JsonResponse(['error' => 'Trajet introuvable'], 404);
        }

        // Empêcher les doublons
        $existing = $em->getRepository(Participe::class)->findOneBy([
            'utilisateur' => $user,
            'covoiturage' => $ride
        ]);

        if ($existing) {
            return new JsonResponse(['error' => 'Déjà inscrit'], 409);
        }

        // Enregistrer la participation
        $participe = new Participe();
        $participe->setUtilisateur($user);
        $participe->setCovoiturage($ride);
        $participe->setStatut('à venir');

        $em->persist($participe);
        $em->flush();

        // ✅ Historique lisible enregistré dans MongoDB
        $this->rideHistoryLogger->logRideParticipation($ride, $user);

        return new JsonResponse(['success' => true]);
    }
}