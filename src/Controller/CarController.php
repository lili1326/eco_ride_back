<?php

namespace App\Controller;

use App\Entity\Car;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
 
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CarRepository;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface; 
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use DateTimeImmutable; 

 #[Route('/api/car', name: 'app_api_car_')]
final class CarController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private CarRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }
    #[Route(methods: 'POST')]
    public function new(Request $request): JsonResponse
    {              
            // 1. Récupération de l'utilisateur connecté
    $owner = $this->getUser();
    if (!$owner) {
        return new JsonResponse(['error' => 'Utilisateur non authentifié'], 401);
    }

    // 2. Désérialisation avec le groupe 'car:write'
    $car = $this->serializer->deserialize(
        $request->getContent(),
        Car::class,
        'json',
        ['groups' => ['car:write'],
        'enable_max_depth' => true,]
        
    );

    // 3. Hydratation des champs dynamiques
    $car->setCreatedAt(new \DateTimeImmutable());
    $car->setOwner($owner);

    // 4. Sauvegarde
    $this->manager->persist($car);
    $this->manager->flush();

    // 5. Sérialisation avec le groupe 'car:read'
    $responseData = $this->serializer->serialize(
        $car,
        'json',
        ['groups' => ['car:read'],
        'enable_max_depth' => true,]
    );

    // 6. Réponse
    $location = $this->urlGenerator->generate(
        'app_api_car_show',
        ['id' => $car->getId()],
        UrlGeneratorInterface::ABSOLUTE_URL
    );

    return new JsonResponse($responseData, Response::HTTP_CREATED, ['Location' => $location], true);
}
    
#[Route('/mes-vehicules', name: 'mes_vehicules', methods: ['GET'])]
public function mesVehicules(): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
    }

    $cars = $this->repository->findBy(['owner' => $user]);

    $json = $this->serializer->serialize(
        $cars,
        'json',
        ['groups' => ['car:read'], 'enable_max_depth' => true]
    );

    return new JsonResponse($json, Response::HTTP_OK, [], true);
}


    #[Route('/{id}', name: 'show', methods: 'GET')]
    public function show(int $id): JsonResponse
    {
        $car = $this->repository->findOneBy(['id' => $id]);
        if ($car) {
            $responseData = $this->serializer->serialize(
                $car,
                'json',
                [
                    'groups' => ['car:read'],
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
        $car = $this->repository->find($id);
        if (!$car) {
            return new JsonResponse(['error' => 'Véhicule non trouvé'], Response::HTTP_NOT_FOUND);
        }
    
        // Mise à jour des données avec le groupe ride:write
        $this->serializer->deserialize(
            $request->getContent(),
            Car::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $car,
                'groups' => ['car:write'],
            ]
        );
    
        $car->setUpdateAt(new DateTimeImmutable());
        $this->manager->flush();
    
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


         
        #[Route('/{id}', name: 'delete', methods: 'DELETE')]
    public function delete(int $id): JsonResponse
    {
        $car = $this->repository->findOneBy(['id' => $id]);
        if ($car) {
            $this->manager->remove($car);
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    

    }
       