<?php

namespace App\Controller;

use App\Entity\Admin;
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

#[Route('/api/admin', name: 'admin_api_')]
class AdminSecurityController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private SerializerInterface $serializer,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    #[OA\Post(
        path: '/api/admin/register',
        summary: "Inscription d'un nouvel admin",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'admin@mail.com'),
                    new OA\Property(property: 'pseudo', type: 'string', example: 'SuperAdmin'),
                    new OA\Property(property: 'password', type: 'string', example: 'AdminPassword123'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Admin créé avec succès')]
    )]
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $admin = $this->serializer->deserialize($request->getContent(), Admin::class, 'json');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $admin->getPassword()));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setApiToken(bin2hex(random_bytes(20))); //  Génération du token

        $this->manager->persist($admin);
        $this->manager->flush();

        return new JsonResponse([
            'admin' => $admin->getUserIdentifier(),
            'roles' => $admin->getRoles(),
            'token' => $admin->getApiToken()
        ], Response::HTTP_CREATED);
    }

    #[OA\Post(
        path: '/api/admin/login',
        summary: "Connexion d'un admin",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'admin@mail.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'AdminPassword123'),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Connexion réussie')]
    )]
    #[Route('/login', name: 'login', methods: ['POST'])]
public function login(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    $email = $data['username'] ?? null;
    $password = $data['password'] ?? null;

    if (!$email || !$password) {
        return new JsonResponse(['message' => 'Identifiants manquants'], Response::HTTP_BAD_REQUEST);
    }

    $admin = $this->manager->getRepository(Admin::class)->findOneBy(['email' => $email]);

    if (!$admin) {
        return new JsonResponse(['message' => 'Admin introuvable'], Response::HTTP_UNAUTHORIZED);
    }

    if (!$this->passwordHasher->isPasswordValid($admin, $password)) {
        return new JsonResponse(['message' => 'Mot de passe invalide'], Response::HTTP_UNAUTHORIZED);
    }

     
    if (!$admin->getApiToken()) {
        $admin->setApiToken(bin2hex(random_bytes(20)));
        $this->manager->flush();
    }

    return new JsonResponse([
        'admin' => $admin->getEmail(),
        'token' => $admin->getApiToken(),
        'roles' => $admin->getRoles()
    ]);
}


    #[OA\Get(
        path: '/api/admin/me',
        summary: "Infos du compte admin connecté",
        responses: [new OA\Response(response: 200, description: 'Infos du compte')]
    )]
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $this->getUser();

        $data = $this->serializer->serialize($admin, 'json');

        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }
    #[OA\Put(
        path: '/api/admin/edit',
        summary: "Met à jour les informations de l'admin connecté",
        security: [['X-AUTH-TOKEN' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'admin@nouveau.com'),
                    new OA\Property(property: 'pseudo', type: 'string', example: 'AdminPro'),
                    new OA\Property(property: 'password', type: 'string', example: 'NouveauMotDePasse123')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: 'Mise à jour réussie'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 400, description: 'Données invalides')
        ]
    )]
    #[Route('/edit', name: 'edit', methods: ['PUT'])]
    public function edit(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $this->getUser();

        // Mise à jour des champs depuis le JSON
        $this->serializer->deserialize(
            $request->getContent(),
            Admin::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $admin]
        );

        $data = json_decode($request->getContent(), true);

        if (!empty($data['password'])) {
            $admin->setPassword($this->passwordHasher->hashPassword($admin, $data['password']));
        }

        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }



}