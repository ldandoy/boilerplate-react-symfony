<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\EmailVerification;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerificationProcessor implements ProcessorInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): EmailVerification
    {
        /** @var EmailVerification $data */
        $user = $this->userRepository->find($data->userId);

        if (!$user) {
            throw new NotFoundHttpException('User not found.');
        }

        try {
            $this->verifyEmailHelper->validateEmailConfirmation(
                $data->signedUrl,
                (string) $user->getId(),
                (string) $user->getEmail()
            );

            $user->setIsVerified(true);
            $this->entityManager->flush();
        } catch (VerifyEmailExceptionInterface $exception) {
            throw new BadRequestHttpException($exception->getReason());
        }

        $response = new EmailVerification();
        $response->userId = $user->getId();
        $response->verified = true;
        $response->message = 'Email verified successfully.';

        return $response;
    }
}
