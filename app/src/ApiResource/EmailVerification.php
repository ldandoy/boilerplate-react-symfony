<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\EmailVerificationProcessor;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/verify/email',
            processor: EmailVerificationProcessor::class,
            read: false,
            openapi: new \ApiPlatform\OpenApi\Model\Operation(
                tags: ['Authentication']
            )
        )
    ],
    output: false
)]
class EmailVerification
{
}
