<?php

namespace App\EventListener;

use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;

#[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_success', priority: 10)]
class AuthenticationSuccessListener
{
    public function __construct(
        private RefreshTokenManagerInterface $refreshTokenManager,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private int $ttl = 2592000, // 30 jours
        private string $tokenParameterName = 'refreshToken',
    ) {
    }

    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        // Generer le refresh token et le sauvegarder en base de donnees
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, $this->ttl);
        $this->refreshTokenManager->save($refreshToken);
        $refreshTokenString = $refreshToken->getRefreshToken();

        // Ajouter le refresh token au corps de la reponse
        $data = $event->getData();
        $data[$this->tokenParameterName] = $refreshTokenString;
        $event->setData($data);

        // Creer un cookie HTTP-only securise
        $event->getResponse()->headers->setCookie(
            new Cookie(
                $this->tokenParameterName,
                $refreshTokenString,
                time() + $this->ttl,
                '/',
                null,
                true,  // secure
                true,  // httpOnly
                false, // raw
                'lax', // sameSite
                false  // partitioned
            )
        );
    }
}
