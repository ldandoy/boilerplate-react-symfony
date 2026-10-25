<?php

namespace App\Tests\Functional;

class ApiAccessTest extends ApiTestCase
{
    public function testSwaggerUiIsPublic(): void
    {
        $this->client->request('GET', '/api', ['headers' => ['Accept' => 'text/html']]);

        $this->assertResponseIsSuccessful();
    }

    public function testOpenApiSpecIsPublic(): void
    {
        $this->client->request('GET', '/api/docs.jsonopenapi', ['headers' => ['Accept' => 'application/vnd.openapi+json']]);

        $this->assertResponseIsSuccessful();
    }

    public function testApiRoutesRequireJwtByDefault(): void
    {
        // Route API Platform sans règle de sécurité propre : protégée par le access_control ^/api
        $this->client->request('GET', '/api/.well-known/genid/1');

        $this->assertResponseStatusCodeSame(401);
    }
}
