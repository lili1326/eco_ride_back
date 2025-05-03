<?php

namespace App\Controller;
 

use App\Entity\Ride;
use App\Repository\RideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use DateTimeImmutable; 
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface; 

 use App\Entity\User;

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

    #[Route(methods: 'POST')]
    /** @OA\Post(
     *     path="/api/restaurant",
     *     summary="Créer un restaurant",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Données du restaurant à créer",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", example="Nom du restaurant"),
     *             @OA\Property(property="description", type="string", example="Description du restaurant")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Restaurant créé avec succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nom du restaurant"),
     *             @OA\Property(property="description", type="string", example="Description du restaurant"),
     *             @OA\Property(property="createdAt", type="string", format="date-time")
     *         )
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
    $location = $this->urlGenerator->generate(
        'app_api_ride_show',
        ['id' => $ride->getId()],
        UrlGeneratorInterface::ABSOLUTE_URL
    );

    return new JsonResponse($responseData, Response::HTTP_CREATED, ['Location' => $location], true);
}
 

    
    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): JsonResponse
    {
        $ride = $this->repository->findOneBy(['id' => $id]);
        if ($ride) {
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

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'edit', methods: 'PUT')]
    public function edit(int $id, Request $request): JsonResponse
    {
        $ride = $this->repository->find($id);
        if (!$ride) {
            return new JsonResponse(['error' => 'Trajet non trouvé'], Response::HTTP_NOT_FOUND);
        }
    
        // Mise à jour des données avec le groupe ride:write
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
     
    #[Route('/{id}', name: 'delete', methods: 'DELETE')]
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
}