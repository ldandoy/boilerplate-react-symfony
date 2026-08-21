<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\RegisterInput;
use App\ApiResource\RegisterOutput;
use App\Service\UserRegistrationService;

class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRegistrationService $registrationService
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RegisterOutput
    {
        if (!$data instanceof RegisterInput) {
            throw new \InvalidArgumentException('Unsupported data type');
        }

        $user = $this->registrationService->registerUser($data->email, $data->password, 'api_verify_email');

        return new RegisterOutput(
            id: $user->getId(),
            email: $user->getEmail()
        );
    }
}
