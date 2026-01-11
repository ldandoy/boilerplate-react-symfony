<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\RegisterInput;
use App\ApiResource\RegisterOutput;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RegisterOutput
    {
        if ($data instanceof RegisterInput) {
            return $this->processRegistration($data);
        }

        throw new \InvalidArgumentException('Unsupported data type');
    }

    private function processRegistration(RegisterInput $input): RegisterOutput
    {
        $user = new User();
        $user->setEmail($input->email);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $input->password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return new RegisterOutput(
            id: $user->getId(),
            email: $user->getEmail()
        );
    }
}

