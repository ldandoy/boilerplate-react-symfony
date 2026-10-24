<?php

namespace App\Tests\Functional;

use App\Entity\RefreshToken;

class AuthenticationTest extends ApiTestCase
{
    // --- POST /api/login_check ---

    public function testLoginReturnsJwtAndRefreshToken(): void
    {
        $this->createUser();

        $response = $this->client->request('POST', '/api/login_check', [
            'json' => ['email' => 'user@example.com', 'password' => self::PASSWORD],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('refreshToken', $data);
        $this->assertCount(3, explode('.', $data['token']));
        $this->assertStringContainsString('refreshToken=', $response->getHeaders()['set-cookie'][0]);
        $this->assertCount(1, $this->getEntityManager()->getRepository(RefreshToken::class)->findAll());
    }

    public function testLoginWithWrongPasswordIsRejected(): void
    {
        $this->createUser();

        $this->client->request('POST', '/api/login_check', [
            'json' => ['email' => 'user@example.com', 'password' => 'wrong-password'],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginWithUnknownEmailIsRejected(): void
    {
        $this->client->request('POST', '/api/login_check', [
            'json' => ['email' => 'nobody@example.com', 'password' => self::PASSWORD],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginWithMissingFieldsIsRejected(): void
    {
        $this->client->request('POST', '/api/login_check', [
            'json' => ['email' => 'user@example.com'],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testLoginIsThrottledAfterTooManyAttempts(): void
    {
        $this->createUser();

        for ($i = 0; $i < 5; ++$i) {
            $this->client->request('POST', '/api/login_check', [
                'json' => ['email' => 'user@example.com', 'password' => 'wrong-password'],
            ]);
        }

        // Même avec le bon mot de passe, la 6e tentative est bloquée
        $this->client->request('POST', '/api/login_check', [
            'json' => ['email' => 'user@example.com', 'password' => self::PASSWORD],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    // --- POST /api/token/refresh ---

    public function testRefreshReturnsNewJwt(): void
    {
        $this->createUser();
        $tokens = $this->login();

        $response = $this->client->request('POST', '/api/token/refresh', [
            'json' => ['refreshToken' => $tokens['refreshToken']],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $response->toArray());
    }

    public function testRefreshWithInvalidTokenIsRejected(): void
    {
        $this->client->request('POST', '/api/token/refresh', [
            'json' => ['refreshToken' => 'invalid-refresh-token'],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRefreshWithoutTokenIsRejected(): void
    {
        $this->client->request('POST', '/api/token/refresh', ['json' => []]);

        $this->assertResponseStatusCodeSame(401);
    }

    // --- POST /api/token/invalidate ---

    public function testLogoutRevokesRefreshToken(): void
    {
        $this->createUser();
        $tokens = $this->login();

        $response = $this->client->request('POST', '/api/token/invalidate', [
            'auth_bearer' => $tokens['token'],
            'json' => ['refreshToken' => $tokens['refreshToken']],
        ]);

        $this->assertResponseStatusCodeSame(204);
        $this->assertStringContainsString('refreshToken=deleted;', implode("\n", $response->getHeaders()['set-cookie']));
        $this->assertCount(0, $this->getEntityManager()->getRepository(RefreshToken::class)->findAll());

        // Le refresh token révoqué n'est plus utilisable
        $this->client->request('POST', '/api/token/refresh', [
            'json' => ['refreshToken' => $tokens['refreshToken']],
        ]);
        $this->assertResponseStatusCodeSame(401);
    }
}
