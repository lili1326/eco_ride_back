<?php

namespace App\Controller;
 

use App\Entity\Ride;
use App\Repository\RideRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTimeImmutable; 
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface; 



 #[Route('/api/ride', name: 'app_api_ride_')]
final class RideController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private RideRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('',name: 'create', methods: 'POST')]
/**
 * @OA\Post(
 *     path="/api/ride",
 *     summary="Créer un trajet",
 *     tags={"Ride"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="lieu_depart", type="string", example="Lyon"),
 *             @OA\Property(property="lieu_arrivee", type="string", example="Paris"),
 *             @OA\Property(property="date_depart", type="string", format="date", example="2025-06-01"),
 *             @OA\Property(property="heure_depart", type="string", format="time", example="08:00:00"),
 *             @OA\Property(property="date_arrivee", type="string", format="date", example="2025-06-01"),
 *             @OA\Property(property="heure_arrivee", type="string", format="time", example="11:00:00"),
 *             @OA\Property(property="nb_place", type="integer", example=3),
 *             @OA\Property(property="prix_personne", type="integer", example=20)
 *             @OA\Property(property="pseudo", type="string", example="dudu")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Trajet créé",
 *         @OA\JsonContent(type="object")
 *     )
 * )
*/
    public function new(Request $request): JsonResponse
    {              
            // 1. Récupération de l'utilisateur connecté
    $conducteur = $this->getUser();
    if (!$conducteur) {
        return new JsonResponse(['error' => 'Utilisateur non authentifié'], 401);
    }

    // 2. Désérialisation avec le groupe 'ride:write'
    $ride = $this->serializer->deserialize(
        $request->getContent(),
        Ride::class,
        'json',
        ['groups' => ['ride:write'],
        'enable_max_depth' => true,]
        
    );

    // 3. Hydratation des champs dynamiques
    $ride->setCreatedAt(new \DateTimeImmutable());
    $ride->setConducteur($conducteur);

    // 4. Sauvegarde
    $this->manager->persist($ride);
    $this->manager->flush();

    // 5. Sérialisation avec le groupe 'ride:read'
    $responseData = $this->serializer->serialize(
        $ride,
        'json',
        ['groups' => ['ride:read'],
        'enable_max_depth' => true,]
    );

    // 6. Réponse
   // $location = $this->urlGenerator->generate(
      //  'app_api_ride_show', // ← c'est ce nom qu'il faut corriger
       // ['id' => $ride->getId()],
       // UrlGeneratorInterface::ABSOLUTE_URL
   // );

   // return new JsonResponse($responseData, Response::HTTP_CREATED, ['Location' => $location], true);
   return new JsonResponse($responseData, Response::HTTP_CREATED, [], true);
}

    
    #[Route('/{id<\d+>}', name: 'show', methods: 'GET')]
    public function show(int $id): JsonResponse
    {
        $ride = $this->repository->findOneBy(['id' => $id]);
    
        if (!$ride) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }
    
        $responseData = $this->serializer->serialize(
            $ride,
            'json',
            [
                'groups' => ['ride:read'],
                'enable_max_depth' => true,
            ]
        );
    
        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }
    #[Route('/{id<\d+>}', name: 'edit', methods: 'PUT')]

    public function edit(int $id, Request $request): JsonResponse
    {
        $ride = $this->repository->find($id);
        if (!$ride) {
            return new JsonResponse(['error' => 'Trajet non trouvé'], Response::HTTP_NOT_FOUND);
        }
    
         
        $this->serializer->deserialize(
            $request->getContent(),
            Ride::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $ride,
                'groups' => ['ride:write'],
            ]
        );
    
        $ride->setUpdateAt(new DateTimeImmutable());
        $this->manager->flush();
    
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
     
    #[Route('/{id<\d+>}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $ride = $this->repository->findOneBy(['id' => $id]);
        if ($ride) {
            $this->manager->remove($ride);
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/mes-trajets', name: 'mes_trajets', methods: ['GET'])]
    public function mesTrajets(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }
    
        $rides = $this->repository->findBy(['conducteur' => $user]);
    
     
        $json = $this->serializer->serialize(
            $rides,
            'json',
            ['groups' => ['ride:read'], 'enable_max_depth' => true]
        );


        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/public/rides', name: 'public_rides', methods: ['GET'])]
    public function getPublicRides(
        Request $request,
        RideRepository $rideRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $depart = $request->query->get('depart');
        $arrivee = $request->query->get('arrivee');
        $date = $request->query->get('date');
    
        $rides = $rideRepository->findByCriteria($depart, $arrivee, $date);
    
        $json = $serializer->serialize($rides, 'json', ['groups' => 'ride:read']);
        return new JsonResponse($json, 200, [], true); // le `true` ici permet d'envoyer du JSON déjà sérialisé
    }


}