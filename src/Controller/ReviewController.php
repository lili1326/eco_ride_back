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
       