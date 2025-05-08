<?php

namespace App\Controller;

use App\Entity\Review;
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

    #[OA\Post(
        path: '/api/review',
        summary: 'Poster un avis sur un utilisateur ou un covoiturage',
        security: [['X-AUTH-TOKEN' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'note', type: 'integer', example: 4),
                    new OA\Property(property: 'commentaire', type: 'string', example: 'Très bon conducteur.'),
                    new OA\Property(property: 'conducteur', type: 'string', example: '/api/users/3')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Avis créé avec succès',
                content: new OA\JsonContent(type: 'object')
            ),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]

    #[Route(methods: 'POST')]
    public function new(Request $request): JsonResponse
    {              
            // 1. Récupération de l'utilisateur connecté
    $auteur = $this->getUser();
    if (!$auteur) {
        return new JsonResponse(['error' => 'Utilisateur non authentifié'], 401);
    }

    // 2. Désérialisation avec le groupe 'review:write'
    $review = $this->serializer->deserialize(
        $request->getContent(),
        Review::class,
        'json',
        ['groups' => ['review:write'],
        'enable_max_depth' => true,]
        
    );

    // 3. Hydratation des champs dynamiques
    $review->setCreatedAt(new \DateTimeImmutable());
    $review->setAuteur($auteur);

    // 4. Sauvegarde
    $this->manager->persist($review);
    $this->manager->flush();

    // 5. Sérialisation avec le groupe 'review:read'
    $responseData = $this->serializer->serialize(
        $review,
        'json',
        ['groups' => ['review:read'],
        'enable_max_depth' => true,]
    );

    // 6. Réponse
    $location = $this->urlGenerator->generate(
        'app_api_review_show',
        ['id' => $review->getId()],
        UrlGeneratorInterface::ABSOLUTE_URL
    );

    return new JsonResponse($responseData, Response::HTTP_CREATED, ['Location' => $location], true);
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

    

    }
       