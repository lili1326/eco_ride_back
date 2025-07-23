<?php

namespace App\Security;


use App\Repository\UserRepository;
 

use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\{
    AuthenticationException,
    CustomUserMessageAuthenticationException,
    UserNotFoundException
};
use Symfony\Component\Security\Http\Authenticator\{AbstractAuthenticator,
    Passport\Badge\UserBadge,
    Passport\Passport,
    Passport\SelfValidatingPassport};

class ApiTokenAuthenticator extends AbstractAuthenticator
{
// Injection du UserRepository pour rechercher l'utilisateur via le token
    public function __construct(private UserRepository $repository)
    {
    }
// Précise si cette authentification doit être utilisée (si le header est présent)
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }
// Tente d'authentifier l'utilisateur à partir du token
    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->headers->get('X-AUTH-TOKEN');
        if (null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }
        $user = $this->repository->findOneBy(['apiToken' => $apiToken]);
        if (null === $user) {
            throw new UserNotFoundException();
        }
// Crée un passeport validé automatiquement avec le User
        return new SelfValidatingPassport(new UserBadge($user->getUserIdentifier()));
    }
 // Si l’authentification réussit, rien de spécial à faire
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }
// Si elle échoue, on renvoie une réponse JSON avec message et code 401
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['message' => strtr($exception->getMessageKey(), $exception->getMessageData())],
            Response::HTTP_UNAUTHORIZED
        );
    }
}