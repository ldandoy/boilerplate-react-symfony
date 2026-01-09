<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\UserRegistrationProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/register',
            processor: UserRegistrationProcessor::class,
            status: 201,
            openapi: new \ApiPlatform\OpenApi\Model\Operation(
                tags: ['Authentication']
            )
        )
    ],
    output: \App\Entity\User::class
)]
class UserRegistration
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public ?string $password = null;
}
