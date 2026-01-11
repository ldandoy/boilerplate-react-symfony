<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ResetPassword;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ResetPassword
    {
        /** @var ResetPassword $data */
        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($data->token);
        } catch (ResetPasswordExceptionInterface $e) {
            throw new BadRequestHttpException($e->getReason());
        }

        // Remove the reset request
        $this->resetPasswordHelper->removeResetRequest($data->token);

        // Hash and set the new password
        $user->setPassword($this->passwordHasher->hashPassword($user, $data->password));
        $this->entityManager->flush();

        $response = new ResetPassword();
        $response->success = true;
        $response->message = 'Password has been reset successfully.';

        return $response;
    }
}
