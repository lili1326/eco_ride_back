<?php

namespace App\Security;

use App\Repository\AdminRepository;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Security\Core\Exception\{
    AuthenticationException,
    CustomUserMessageAuthenticationException,
    UserNotFoundException
};
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\{Passport, SelfValidatingPassport};
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiTokenAdminAuthenticator extends AbstractAuthenticator
{
    public function __construct(private AdminRepository $adminRepository) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-AUTH-TOKEN') &&
               str_starts_with($request->getPathInfo(), '/api/admin');
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $request->headers->get('X-AUTH-TOKEN');

        if (!$apiToken) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        $admin = $this->adminRepository->findOneBy(['apiToken' => $apiToken]);

        if (!$admin) {
            throw new UserNotFoundException();
        }

        return new SelfValidatingPassport(new UserBadge($admin->getUserIdentifier(), fn () => $admin));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null; // Laisser continuer l'exécution normale
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['message' => 'Admin unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}