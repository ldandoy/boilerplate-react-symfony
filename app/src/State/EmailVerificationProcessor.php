<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class EmailVerificationProcessor implements ProcessorInterface
{
    public function __construct(
        private EmailVerifier $emailVerifier,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new BadRequestHttpException('No request available');
        }

        // Récupérer l'ID de l'utilisateur depuis les paramètres de la requête
        $userId = $request->query->get('id');

        if (!$userId) {
            throw new BadRequestHttpException('User ID is required');
        }

        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            throw new BadRequestHttpException('User not found');
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);

            // S'assurer que les changements sont persistés
            $this->entityManager->flush();

            return [
                'message' => 'Your email address has been verified successfully.'
            ];
        } catch (VerifyEmailExceptionInterface $exception) {
            throw new BadRequestHttpException($exception->getReason());
        }
    }
}
