<?php

namespace App\EventListener;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Request\Extractor\ExtractorInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class)]
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
        $response = $event->getResponse();

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
        if ($response) {
            $response->headers->setCookie(
                Cookie::create(self::COOKIE_NAME)
                    ->withValue('')
                    ->withExpires(1)
                    ->withPath('/')
                    ->withHttpOnly(true)
                    ->withSameSite('lax')
            );
        }
    }
}
