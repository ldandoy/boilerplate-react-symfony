<?php

namespace App\Tests\Functional;

class MeTest extends ApiTestCase
{
    public function testMeReturnsAuthenticatedUser(): void
    {
        $user = $this->createUser('admin@example.com', true, ['ROLE_ADMIN']);
        $tokens = $this->login('admin@example.com');

        $this->client->request('GET', '/api/me', ['auth_bearer' => $tokens['token']]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'id' => $user->getId(),
            'email' => 'admin@example.com',
            'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
            'isVerified' => true,
        ]);
    }

    public function testMeWithoutTokenIsUnauthorized(): void
    {
        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeWithInvalidTokenIsUnauthorized(): void
    {
        $this->client->request('GET', '/api/me', ['auth_bearer' => 'not.a.jwt']);

        $this->assertResponseStatusCodeSame(401);
    }
}
