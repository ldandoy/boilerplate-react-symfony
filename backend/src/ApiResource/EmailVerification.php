<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\EmailVerificationProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/verify-email',
            name: 'api_verify_email',
            processor: EmailVerificationProcessor::class,
            openapi: new \ApiPlatform\OpenApi\Model\Operation(
                tags: ['Authentication'],
                summary: 'Verify user email',
                description: 'Validates a user email address using the signed URL from the email.'
            )
        )
    ]
)]
class EmailVerification
{
    #[Assert\NotBlank]
    public ?int $userId = null;

    #[Assert\NotBlank]
    public ?string $signedUrl = null;

    public ?string $message = null;
    public ?bool $verified = null;
}
