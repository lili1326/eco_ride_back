<?php

namespace App\Controller;
 

use App\Entity\Ride;
use App\Repository\RideRepository;
use App\Repository\CarRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTimeImmutable; 
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface; 
use MongoDB\Client as MongoClient;
use Psr\Log\LoggerInterface;
use MongoDB\BSON\UTCDateTime; 


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
 
    #[OA\Post(
        path: '/api/ride',
        summary: "Créer un trajet",
        security: [['X-AUTH-TOKEN' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'lieu_depart', type: 'string', example: 'Lyon'),
                    new OA\Property(property: 'lieu_arrivee', type: 'string', example: 'Paris'),
                    new OA\Property(property: 'date_depart', type: 'string', format: 'date', example: '2025-06-01'),
                    new OA\Property(property: 'heure_depart', type: 'string', format: 'time', example: '08:00:00'),
                    new OA\Property(property: 'heure_arrivee', type: 'string', format: 'time', example: '10:00:00'),
                    new OA\Property(property: 'nb_place', type: 'integer', example: 3),
                    new OA\Property(property: 'prix_personne', type: 'integer', example: 20),
                    new OA\Property(property: 'energie', type: 'string', example: 'Essence'),
                    new OA\Property(property: 'voiture', type: 'string', example: '/api/car/1')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Trajet créé',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 400, description: 'Données invalides')
        ]
    )]
       
    #[Route('',name: 'create', methods: 'POST')]

    public function new(Request $request, CarRepository $carRepo): JsonResponse
    {              
            // 1. Récupération de l'utilisateur connecté
    $conducteur = $this->getUser();
    if (!$conducteur) {
        return new JsonResponse(['error' => 'Utilisateur non authentifié'], 401);
    }

    $data = json_decode($request->getContent(), true);
    $carIri = $data['voiture'] ?? null;
    unset($data['voiture']);


    // 2. Désérialisation avec le groupe 'ride:write'
    $ride = $this->serializer->deserialize(
        $request->getContent(),
        Ride::class,
        'json',
        ['groups' => ['ride:write'],
        'enable_max_depth' => true,]
        
    );
//  Associer la voiture
if ($carIri) {
    $carId = basename($carIri); // "/api/car/4" → "4"
    $car = $carRepo->findOneBy(['id' => $carId, 'owner' => $conducteur]);

    if (!$car) {
        return new JsonResponse(['error' => 'Voiture introuvable'], 404);
    }

    $ride->setVoiture($car);
}


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

    #[OA\Get(
        path: '/api/ride/{id}',
        summary: 'Afficher un trajet par ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Trajet trouvé'),
            new OA\Response(response: 404, description: 'Trajet introuvable')
        ]
    )]

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

    #[OA\Put(
        path: '/api/ride/{id}',
        summary: 'Modifier un trajet',
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'object')
        ),
        responses: [
            new OA\Response(response: 204, description: 'Trajet modifié'),
            new OA\Response(response: 404, description: 'Trajet introuvable')
        ]
    )]

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

    #[OA\Delete(
        path: '/api/ride/{id}',
        summary: 'Supprimer un trajet',
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Trajet supprimé'),
            new OA\Response(response: 404, description: 'Trajet introuvable')
        ]
    )]

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

    #[OA\Get(
        path: '/api/ride/mes-trajets',
        summary: 'Récupère les trajets créés par l’utilisateur connecté',
        security: [['X-AUTH-TOKEN' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des trajets de l’utilisateur',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            ),
            new OA\Response(response: 401, description: 'Utilisateur non authentifié')
        ]
    )]

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

    #[OA\Get(
        path: '/api/ride/public/rides',
        summary: 'Rechercher des trajets publics',
        parameters: [
            new OA\Parameter(name: 'depart', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'arrivee', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des trajets correspondant aux critères',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))
            )
        ]
    )]

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

  #[Route('/{id<\d+>}/statut', name: 'update_statut', methods: ['PUT'])]
public function updateStatut(int $id, Request $request): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return new JsonResponse(['error' => 'Non connecté'], 401);
    }

    $data = json_decode($request->getContent(), true);
    $newStatut = $data['statut'] ?? null;

    if (!in_array($newStatut, ['en_cours', 'termine'])) {
    return new JsonResponse(['error' => 'Statut invalide'], 400);
}

    $ride = $this->repository->find($id);
    if (!$ride || $ride->getConducteur() !== $user) {
        return new JsonResponse(['error' => 'Trajet non autorisé ou introuvable'], 403);
    }

    $ride->setStatut($newStatut);

//  Ajoute ce bloc pour mettre à jour le statut de chaque passager
foreach ($ride->getParticipes() as $p) {
    $p->setStatut($newStatut); // ← c'est ça qui manquait
}

    $this->manager->flush();

    // Simule une notification email (éviter file_put_contents)
    foreach ($ride->getParticipes() as $p) {
        $passager = $p->getUtilisateur();
        $email = $passager->getEmail();
        $prenom = $passager->getFirstName();
        $p->setStatut($newStatut);
        $message = sprintf("Bonjour %s, le trajet est maintenant %s. Merci de laisser un avis.", $prenom, $newStatut);
        // Simule via log ou ignore
        error_log("Notification à $email : $message");
    }

    if ($newStatut === 'termine') {
         $mongo = new MongoClient($_ENV['MONGODB_URL']);
        $wallets = $mongo->selectCollection("covoiturage", "wallet");
        $tresorerie = $mongo->selectCollection("covoiturage", "tresorerie");

        $prix = $ride->getPrixPersonne();
        $conducteurId = $ride->getConducteur()->getId();

        foreach ($ride->getParticipes() as $p) {
            $passagerId = $p->getUtilisateur()->getId();

            //  Mettre à jour le statut du passager aussi
    $p->setStatut($newStatut);

    // Email fictif (déjà présent)
    $message = sprintf(
        "Bonjour %s, le trajet auquel vous avez participé est %s.",
        $passager->getFirstName(),
        $newStatut === 'termine' ? "terminé" : "en cours"
    );

            $wallets->updateOne(
                ['userId' => $passagerId],
                [
                    '$inc' => ['solde' => -$prix],
                    '$push' => ['transactions' => [
                        'type' => 'paiement_trajet',
                        'montant' => -$prix,
                        'trajetId' => $ride->getId(),
                        'date' => new UTCDateTime()
                    ]]
                ]
            );

            $wallets->updateOne(
                ['userId' => $conducteurId],
                [
                    '$inc' => ['solde' => $prix - 2],
                    '$push' => ['transactions' => [
                        'type' => 'recompense_trajet',
                        'montant' => $prix - 2,
                        'trajetId' => $ride->getId(),
                        'date' => new UTCDateTime()
                    ]]
                ]
            );

            $tresorerie->insertOne([
                'trajetId' => $ride->getId(),
                'montant' => 2,
                'date' => new UTCDateTime()
            ]);

        }
    }
     $this->manager->flush();

    return new JsonResponse(['message' => "Statut mis à jour à \"$newStatut\""]);
}
    
}