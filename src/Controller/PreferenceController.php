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
 