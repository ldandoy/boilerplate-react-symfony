<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private string $frontendUrl
    ) {
    }

    public function sendEmailConfirmation(string $verifyEmailRouteName, User $user, TemplatedEmail $email): void
    {
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            (string) $user->getId(),
            (string) $user->getEmail(),
            ['id' => $user->getId()]
        );

        // Get the complete signed URL from the API
        $signedUrl = $signatureComponents->getSignedUrl();

        // Build frontend URL with userId and the complete signed URL (URL encoded)
        $frontendVerifyUrl = rtrim($this->frontendUrl, '/') . '/verify-email?' . http_build_query([
            'userId' => $user->getId(),
            'signedUrl' => $signedUrl,
        ]);

        $context = $email->getContext();
        $context['signedUrl'] = $frontendVerifyUrl;
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        $email->context($context);

        $this->mailer->send($email);
    }
}
