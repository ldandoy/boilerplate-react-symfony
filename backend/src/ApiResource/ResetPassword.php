<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\ResetPasswordProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/reset-password',
            name: 'api_reset_password',
            processor: ResetPasswordProcessor::class,
            openapi: new \ApiPlatform\OpenApi\Model\Operation(
                tags: ['Authentication'],
                summary: 'Reset password',
                description: 'Resets the user password using the token from the email.'
            )
        )
    ]
)]
class ResetPassword
{
    #[Assert\NotBlank]
    public ?string $token = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6)]
    public ?string $password = null;

    public ?string $message = null;
    public ?bool $success = null;
}
