<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\UserRegistration;
use App\Entity\User;
use App\Service\UserRegistrationService;

class UserRegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRegistrationService $registrationService
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        /** @var UserRegistration $data */
        return $this->registrationService->registerUser($data->email, $data->password, 'api');
    }
}
