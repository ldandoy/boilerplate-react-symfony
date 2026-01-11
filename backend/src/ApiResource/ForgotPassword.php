<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\ForgotPasswordProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/forgot-password',
            name: 'api_forgot_password',
            processor: ForgotPasswordProcessor::class,
            openapi: new \ApiPlatform\OpenApi\Model\Operation(
                tags: ['Authentication'],
                summary: 'Request password reset',
                description: 'Sends a password reset email to the user.'
            )
        )
    ]
)]
class ForgotPassword
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    public ?string $message = null;
}
