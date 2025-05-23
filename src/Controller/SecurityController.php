<?php

namespace App\Controller;


use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use MongoDB\Client as MongoClient;
use MongoDB\BSON\UTCDateTime;
use App\Repository\UserRepository;
 
 

#[Route('/api', name: 'app_api_')]
class SecurityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SerializerInterface $serializer,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }
    #[OA\Post(
        path: '/api/registration',
        summary: "Inscription d'un nouvel utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de l'utilisateur à inscrire",
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: 'Thomas'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Dupont'),
                    new OA\Property(property: 'pseudo', type: 'string', example: 'Dudu'),
                    new OA\Property(property: 'email', type: 'string', example: 'thomas@email.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'Motdepasse'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur inscrit avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'user', type: 'string', example: 'Dudu'),
                        new OA\Property(property: 'apiToken', type: 'string', example: 'abc123token'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                    ]
                )
            )
        ]
    )]
        
 
#[Route('/registration', name: 'registration', methods: 'POST')]

public function register(Request $request): JsonResponse
{
    $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
    $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
    $user->setCreatedAt(new DateTimeImmutable());

    try {
    $this->manager->persist($user);
    $this->manager->flush();
} catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
    return new JsonResponse(['error' => 'Email déjà utilisé.'], Response::HTTP_CONFLICT);
}

      //  SEULEMENT si MONGODB_URL est défini (ex: en local ou si bien configuré)
    $mongoUrl = $_ENV['MONGODB_URL'] ?? getenv('MONGODB_URL');
    if ($mongoUrl) {
        try {
    // Création du wallet en MongoDB
    $mongo = new MongoClient('mongodb://localhost:27017');  
    $walletCollection = $mongo->selectCollection('covoiturage', 'wallet');

    $walletCollection->insertOne([
        'userId' => $user->getId(),
        'solde' => 20,
        'transactions' => [[
            'type' => 'bonus_inscription',
            'montant' => 20,
            'date' => new UTCDateTime()
        ]]
    ]);
      } catch (\Throwable $e) {
            //  En prod on ignore l’erreur mongo pour ne pas bloquer l’inscription
            // Optionnel : logger l'erreur
        }
    }

    return new JsonResponse(
        [
            'user'     => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles'    => $user->getRoles(),
        ],
        Response::HTTP_CREATED
    );
}


    #[Route('/login', name: 'login', methods: 'POST')]

    #[OA\Post(
        path: '/api/login',
        summary: " Connecter un utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de l'utilisateur à inscrire",
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'thomas@email.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'Motdepasse'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connection réussie',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'user', type: 'string', example: 'Dudu'),
                        new OA\Property(property: 'apiToken', type: 'string', example: 'abc123token'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                    ]
                )
            )
        ]
    )]
        
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'Missing credentials'], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'user'  => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/account/me', name: 'me', methods: 'GET')]

    #[OA\Get(
        path: '/api/account/me',
        summary: "Récupère les informations du compte connecté",
        security: [['X-AUTH-TOKEN' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Données de l\'utilisateur connecté',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'email', type: 'string', example: 'thomas@email.com'),
                        new OA\Property(property: 'pseudo', type: 'string', example: 'Dudu'),
                        new OA\Property(property: 'firstName', type: 'string', example: 'Thomas'),
                        new OA\Property(property: 'lastName', type: 'string', example: 'Dupont'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
  
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        $responseData = $this->serializer->serialize($user, 'json',['groups' => ['user:read']]);

        return new JsonResponse($responseData, Response::HTTP_OK, [], true);
    }

    #[Route('/account/edit', name: 'edit', methods: 'PUT')]

    #[OA\Put(
        path: '/api/account/edit',
        summary: "Met à jour les informations du compte connecté",
        security: [['X-AUTH-TOKEN' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'firstName', type: 'string', example: 'Thomas'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Dupont'),
                    new OA\Property(property: 'pseudo', type: 'string', example: 'Toto21'),
                    new OA\Property(property: 'email', type: 'string', example: 'nouvel@email.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'NouveauMotDePasse')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: 'Mise à jour réussie'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 400, description: 'Données invalides')
        ]
    )]
   
    public function edit(Request $request): JsonResponse
    {
        $user = $this->serializer->deserialize(
            $request->getContent(),
            User::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $this->getUser()],
        );
        $user->setUpdatedAt(new DateTimeImmutable());

        if (isset($request->toArray()['password'])) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
        }

        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

  #[Route('/wallet', name: 'wallet_show', methods: ['GET'])]
public function showWallet(): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return new JsonResponse(['error' => 'Non authentifié'], 401);
    }

    $mongo = new MongoClient('mongodb://localhost:27017'); // adapte si nécessaire
    $walletCollection = $mongo->selectCollection('covoiturage', 'wallet');

         /** @var \App\Entity\User|null $user */
    $wallet = $walletCollection->findOne(['userId' => $user->getId()]);
    if (!$wallet) {
        return new JsonResponse(['error' => 'Portefeuille introuvable'], 404);
    }

    return new JsonResponse([
        'solde' => $wallet['solde'],
        'transactions' => $wallet['transactions'],
    ]);
}  

#[Route('/users/{id}', name: 'api_user_show', methods: ['GET'])]
public function show(int $id, UserRepository $repo, SerializerInterface $serializer): JsonResponse
{
    $user = $repo->find($id);

    if (!$user) {
        return new JsonResponse(['error' => 'Utilisateur introuvable'], 404);
    }

    $json = $serializer->serialize($user, 'json', ['groups' => ['user:read']]);

    return new JsonResponse($json, 200, [], true);
}

}