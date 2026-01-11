<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\ForgotPassword;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ForgotPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private MailerInterface $mailer,
        private string $frontendUrl
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ForgotPassword
    {
        /** @var ForgotPassword $data */
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $data->email,
        ]);

        $response = new ForgotPassword();
        $response->message = 'If an account exists with this email, a password reset link has been sent.';

        if (!$user) {
            return $response;
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            return $response;
        }

        $resetUrl = rtrim($this->frontendUrl, '/') . '/reset-password?token=' . $resetToken->getToken();

        $email = (new Email())
            ->from('mailer@test.com')
            ->to((string) $user->getEmail())
            ->subject('Password Reset Request')
            ->html(sprintf(
                '<p>Click the link below to reset your password:</p><p><a href="%s">%s</a></p><p>This link will expire in %d hour(s).</p>',
                $resetUrl,
                $resetUrl,
                $this->resetPasswordHelper->getTokenLifetime() / 3600
            ));

        $this->mailer->send($email);

        return $response;
    }
}
