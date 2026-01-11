<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Model;

final class JwtDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();

        $schemas['Token'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
            ],
        ]);

        $schemas['Credentials'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'email' => [
                    'type' => 'string',
                    'example' => 'john.doe@example.com',
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'apassword',
                ],
            ],
        ]);

        $schemas['RefreshToken'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'refresh_token' => [
                    'type' => 'string',
                ],
            ],
        ]);

        $schemas['RefreshTokenResponse'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'refresh_token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
            ],
        ]);

        $pathItem = new Model\PathItem(
            ref: 'JWT Token Refresh',
            post: new Model\Operation(
                operationId: 'postRefreshToken',
                tags: ['Authentication'],
                responses: [
                    '200' => [
                        'description' => 'Returns new JWT token and refresh token',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    '$ref' => '#/components/schemas/RefreshTokenResponse',
                                ],
                            ],
                        ],
                    ],
                    '401' => [
                        'description' => 'Invalid refresh token',
                    ],
                ],
                summary: 'Refresh JWT token',
                description: 'Refresh an expired JWT token using a valid refresh token',
                requestBody: new Model\RequestBody(
                    description: 'Refresh token',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/RefreshToken',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath('/api/token/refresh', $pathItem);

        $pathItem = new Model\PathItem(
            ref: 'JWT Token Invalidate',
            post: new Model\Operation(
                operationId: 'postInvalidateToken',
                tags: ['Authentication'],
                responses: [
                    '200' => [
                        'description' => 'Refresh token invalidated',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'message' => [
                                            'type' => 'string',
                                            'example' => 'Refresh token invalidated',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '401' => [
                        'description' => 'Invalid refresh token',
                    ],
                ],
                summary: 'Invalidate refresh token',
                description: 'Invalidate a refresh token to log out a user',
                requestBody: new Model\RequestBody(
                    description: 'Refresh token to invalidate',
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/RefreshToken',
                            ],
                        ],
                    ]),
                ),
            ),
        );
        $openApi->getPaths()->addPath('/api/token/invalidate', $pathItem);

        // Modifier le tag de la route /api/login_check pour la mettre dans Authentication
        $loginPath = $openApi->getPaths()->getPath('/api/login_check');

        if ($loginPath && $loginPath->getPost()) {
            $operation = $loginPath->getPost();

            $newOperation = new Model\Operation(
                operationId: $operation->getOperationId(),
                tags: ['Authentication'],
                responses: $operation->getResponses(),
                summary: $operation->getSummary(),
                description: $operation->getDescription(),
                requestBody: $operation->getRequestBody(),
                parameters: $operation->getParameters(),
            );

            $openApi->getPaths()->addPath('/api/login_check', $loginPath->withPost($newOperation));
        }

        return $openApi;
    }
}
