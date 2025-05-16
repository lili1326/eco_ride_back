<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\User;
use App\Entity\Ride;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTimeImmutable;  
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface; 
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use OpenApi\Attributes as OA;
 

    #[Route('/api/review', name: 'app_api_review_')]
final class ReviewController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private ReviewRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    
#[Route(methods: 'POST')]
public function new(Request $request): JsonResponse
{
    $auteur = $this->getUser();
    if (!$auteur) {
        return new JsonResponse(['error' => 'Utilisateur non authentifié'], 401);
    }

    $data = json_decode($request->getContent(), true);

    if (!isset($data['note'], $data['commentaire'], $data['conducteur'], $data['covoiturage'])) {
        return new JsonResponse(['error' => 'Champs requis manquants'], 400);
    }

    $conducteurId = basename($data['conducteur']);
    $rideId = basename($data['covoiturage']);

    $conducteur = $this->manager->getRepository(User::class)->find($conducteurId);
    $ride = $this->manager->getRepository(Ride::class)->find($rideId);

    if (!$conducteur || !$ride) {
        return new JsonResponse(['error' => 'Conducteur ou trajet introuvable'], 404);
    }

    if ($ride->getStatut() !== 'termine') {
        return new JsonResponse(['error' => 'Ce trajet n’est pas encore terminé.'], 403);
    }

    // Vérifie que l'utilisateur a bien participé à ce trajet
    $participeRepo = $this->manager->getRepository(\App\Entity\Participe::class);
    $participation = $participeRepo->findOneBy([
        'covoiturage' => $ride,
        'utilisateur' => $auteur,
        'statut' => 'termine',
    ]);

    if (!$participation) {
        return new JsonResponse(['error' => 'Vous n’avez pas participé à ce trajet.'], 403);
    }

    // Empêche les doublons d'avis
    $existing = $this->repository->findOneBy([
        'auteur' => $auteur,
        'covoiturage' => $ride
    ]);

    if ($existing) {
        return new JsonResponse(['error' => 'Vous avez déjà laissé un avis sur ce trajet.'], 409);
    }

    $review = new Review();
    $review->setNote((int) $data['note']);
    $review->setCommentaire($data['commentaire']);
    $review->setAuteur($auteur);
    $review->setConducteur($conducteur);
    $review->setCovoiturage($ride);
    $review->setCreatedAt(new \DateTimeImmutable());

    $this->manager->persist($review);
    $this->manager->flush();

    $json = $this->serializer->serialize(
        $review,
        'json',
        ['groups' => ['review:read']]
    );

    return new JsonResponse($json, JsonResponse::HTTP_CREATED, [], true);
}

   
    
#[Route('/mes-avis', name: 'mes_avis', methods: ['GET'])]

#[OA\Get(
    path: '/api/review/mes-avis',
    summary: 'Récupérer mes avis donnés',
    security: [['X-AUTH-TOKEN' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Liste des avis', content: new OA\JsonContent(type: 'array', items: new OA\Items(type: 'object'))),
        new OA\Response(response: 401, description: 'Non authentifié')
    ]
)]

public function mesAvis(): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
    }

    $review = $this->repository->findBy(['auteur' => $user]);

    $json = $this->serializer->serialize(
        $review,
        'json',
        ['groups' => ['review:read'], 'enable_max_depth' => true]
    );

    return new JsonResponse($json, Response::HTTP_OK, [], true);
}



#[Route('/recus', name: 'recus', methods: ['GET'])]
public function avisRecus(): JsonResponse
{
    $conducteur = $this->getUser();
    if (!$conducteur) {
        return new JsonResponse(['error' => 'Non authentifié'], 401);
    }

    $avis = $this->repository->findBy(['conducteur' => $conducteur]);

    $json = $this->serializer->serialize(
        $avis,
        'json',
        ['groups' => ['review:read'], 'enable_max_depth' => true]
    );

    return new JsonResponse($json, 200, [], true);
}









    #[Route('/{id}', name: 'show', methods: 'GET')]

    #[OA\Get(
        path: '/api/review/{id}',
        summary: 'Récupérer un avis par ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Avis trouvé'),
            new OA\Response(response: 404, description: 'Avis introuvable')
        ]
    )]

    public function show(int $id): JsonResponse
    {
        $review = $this->repository->findOneBy(['id' => $id]);
        if ($review) {
            $responseData = $this->serializer->serialize(
                $review,
                'json',
                [
                    'groups' => ['review:read'],
                    'enable_max_depth' => true,
                ]
            );

            return new JsonResponse($responseData, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }
    
    #[Route('/{id}', name: 'edit', methods: ['PUT'])]

    #[OA\Put(
        path: '/api/review/{id}',
        summary: 'Modifier un avis existant',
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'note', type: 'integer', example: 5),
                    new OA\Property(property: 'commentaire', type: 'string', example: 'Parfait !')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: 'Modifié avec succès'),
            new OA\Response(response: 404, description: 'Introuvable')
        ]
    )]

    public function edit(int $id, Request $request): JsonResponse
    {
        $review = $this->repository->find($id);
        if (!$review) {
            return new JsonResponse(['error' => 'Avis non trouvé'], Response::HTTP_NOT_FOUND);
        }
    
        $this->serializer->deserialize(
            $request->getContent(),
            Review::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $review,
                'groups' => ['review:write'],
            ]
        );
    
        $this->manager->flush();
    
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
         
        #[Route('/{id}', name: 'delete', methods: 'DELETE')]

        #[OA\Delete(
            path: '/api/review/{id}',
            summary: 'Supprimer un avis',
            security: [['X-AUTH-TOKEN' => []]],
            parameters: [
                new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
            ],
            responses: [
                new OA\Response(response: 204, description: 'Avis supprimé'),
                new OA\Response(response: 404, description: 'Avis introuvable')
            ]
        )]

    public function delete(int $id): JsonResponse
    {
        $review = $this->repository->findOneBy(['id' => $id]);
        if ($review) {
            $this->manager->remove($review);
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

 #[Route('/conducteur/{id}', name: 'avis_conducteur', methods: ['GET'])]
public function avisParConducteur(int $id): JsonResponse
{
    $conducteur = $this->manager->getRepository(User::class)->find($id);

    if (!$conducteur) {
        return new JsonResponse(['error' => 'Conducteur introuvable'], 404);
    }

    $avis = $this->repository->findBy(['conducteur' => $conducteur]);

    $json = $this->serializer->serialize(
        $avis,
        'json',
        ['groups' => ['review:read'], 'enable_max_depth' => true]
    );

    return new JsonResponse($json, 200, [], true);
}


    }
       