<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\MeProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/me',
            name: 'api_me',
            provider: MeProvider::class,
            openapi: new \ApiPlatform\OpenApi\Model\Operation(
                tags: ['User'],
                summary: 'Get current user',
                description: 'Returns the authenticated user information.'
            )
        )
    ]
)]
class Me
{
    public ?int $id = null;
    public ?string $email = null;
    public ?array $roles = null;
    public ?bool $isVerified = null;
}
