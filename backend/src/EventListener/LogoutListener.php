<?php

namespace App\EventListener;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Request\Extractor\ExtractorInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

// Priorité > 64 : passe avant le DefaultLogoutListener qui redirigerait vers "/"
#[AsEventListener(event: LogoutEvent::class, priority: 128, dispatcher: 'security.event_dispatcher.api')]
class LogoutListener
{
    private const COOKIE_NAME = 'refreshToken';

    public function __construct(
        private RefreshTokenManagerInterface $refreshTokenManager,
        private ExtractorInterface $extractor
    ) {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse() ?? new Response(null, Response::HTTP_NO_CONTENT);

        // Extract refresh token from request (cookie or body)
        $refreshToken = $this->extractor->getRefreshToken($request, 'refreshToken');

        if ($refreshToken) {
            // Delete the refresh token from database
            $token = $this->refreshTokenManager->get($refreshToken);
            if ($token) {
                $this->refreshTokenManager->delete($token);
            }
        }

        // Clear the refresh token cookie
        $response->headers->setCookie(
            Cookie::create(self::COOKIE_NAME)
                ->withValue('')
                ->withExpires(1)
                ->withPath('/')
                ->withHttpOnly(true)
                ->withSameSite('lax')
        );

        $event->setResponse($response);
    }
}
