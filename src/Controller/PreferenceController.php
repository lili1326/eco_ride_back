<?php

namespace App\Controller;

use App\Entity\Preference;
use App\Repository\PreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use DateTimeImmutable;
use OpenApi\Attributes as OA;

#[Route('/api/preference', name: 'app_api_preference_')]
final class PreferenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $manager,
        private PreferenceRepository $repository,
        private SerializerInterface $serializer,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[OA\Post(
        path: '/api/preference',
        summary: 'Créer une préférence utilisateur',
        security: [['X-AUTH-TOKEN' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'fumeur', type: 'string', example: 'non'),
                    new OA\Property(property: 'animaux', type: 'string', example: 'oui'),
                    new OA\Property(property: 'musique', type: 'string', example: 'Classique'),
                    new OA\Property(property: 'description', type: 'string', example: 'Préférence pour la musique douce.')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Préférence enregistrée'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]

    #[Route('', name: 'new', methods: 'POST')]
    public function new(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], 401);
        }

        $preference = $this->serializer->deserialize(
            $request->getContent(),
            Preference::class,
            'json',
            ['groups' => ['preference:write'], 'enable_max_depth' => true]
        );

        $preference->setUtilisateur($user);
        $preference->setCreatedAt(new DateTimeImmutable());

        $this->manager->persist($preference);
        $this->manager->flush();

        $responseData = $this->serializer->serialize(
            $preference,
            'json',
            ['groups' => ['preference:read'], 'enable_max_depth' => true]
        );

        $location = $this->urlGenerator->generate(
            'app_api_preference_show',
            ['id' => $preference->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new JsonResponse($responseData, Response::HTTP_CREATED, ['Location' => $location], true);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]

    #[OA\Get(
        path: '/api/preference/me',
        summary: 'Afficher la préférence actuelle de l’utilisateur connecté',
        security: [['X-AUTH-TOKEN' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Préférence trouvée'),
            new OA\Response(response: 404, description: 'Aucune préférence enregistrée'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]

    public function userPreference(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $pref = $this->repository->findOneBy(['utilisateur' => $user]);
        if (!$pref) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize(
            $pref,
            'json',
            ['groups' => ['preference:read'], 'enable_max_depth' => true]
        );

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'show', methods: 'GET')]

    #[OA\Get(
        path: '/api/preference/{id}',
        summary: 'Afficher une préférence par ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Préférence trouvée'),
            new OA\Response(response: 404, description: 'Non trouvée')
        ]
    )]

    public function show(int $id): JsonResponse
    {
        $pref = $this->repository->find($id);
        if (!$pref) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize(
            $pref,
            'json',
            ['groups' => ['preference:read'], 'enable_max_depth' => true]
        );

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'edit', methods: 'PUT')]

    #[OA\Put(
        path: '/api/preference/{id}',
        summary: 'Modifier une préférence',
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'fumeur', type: 'string', example: 'oui'),
                    new OA\Property(property: 'animaux', type: 'string', example: 'non'),
                    new OA\Property(property: 'musique', type: 'string', example: 'Jazz'),
                    new OA\Property(property: 'description', type: 'string', example: 'Pas de musique trop forte svp.')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: 'Préférence modifiée'),
            new OA\Response(response: 404, description: 'Non trouvée')
        ]
    )]

    public function edit(int $id, Request $request): JsonResponse
    {
        $pref = $this->repository->find($id);
        if (!$pref) {
            return new JsonResponse(['error' => 'Préférence non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $this->serializer->deserialize(
            $request->getContent(),
            Preference::class,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $pref,
                'groups' => ['preference:write'],
            ]
        );

        $pref->setUpdateAt(new DateTimeImmutable());
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', name: 'delete', methods: 'DELETE')]

    #[OA\Delete(
        path: '/api/preference/{id}',
        summary: 'Supprimer une préférence',
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Préférence supprimée'),
            new OA\Response(response: 404, description: 'Non trouvée')
        ]
    )]

    public function delete(int $id): JsonResponse
    {
        $pref = $this->repository->find($id);
        if (!$pref) {
            return new JsonResponse(null, Response::HTTP_NOT_FOUND);
        }

        $this->manager->remove($pref);
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
 