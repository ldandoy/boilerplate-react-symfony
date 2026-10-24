<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Entity\User;
use App\State\UserProcessor;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/register',
            processor: UserProcessor::class,
            output: RegisterOutput::class,
            openapi: new \ApiPlatform\OpenApi\Model\Operation(
                tags: ['Authentication'],
                summary: 'Register a new user',
                description: 'Create a new user account with email and password'
            )
        )
    ]
)]
#[UniqueEntity(fields: ['email'], entityClass: User::class, message: 'There is already an account with this email')]
class RegisterInput
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email(message: 'Please provide a valid email address')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(
        min: 8,
        max: 4096,
        minMessage: 'Password must be at least {{ limit }} characters long',
        maxMessage: 'Password cannot be longer than {{ limit }} characters'
    )]
    public ?string $password = null;
}
