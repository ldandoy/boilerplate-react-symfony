<?php

namespace App\Tests\Unit\State;

use App\ApiResource\ForgotPassword;
use App\Entity\User;
use App\State\ForgotPasswordProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ForgotPasswordProcessorTest extends TestCase
{
    private function createProcessor(
        EntityManagerInterface $entityManager,
        ResetPasswordHelperInterface $resetPasswordHelper,
        MailerInterface $mailer
    ): ForgotPasswordProcessor {
        return new ForgotPasswordProcessor($entityManager, $resetPasswordHelper, $mailer, 'http://localhost:5173');
    }

    public function testUnknownEmailReturnsGenericMessageWithoutSendingMail(): void
    {
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        $resetPasswordHelper = $this->createMock(ResetPasswordHelperInterface::class);
        $resetPasswordHelper->expects($this->never())->method('generateResetToken');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');

        $processor = $this->createProcessor($entityManager, $resetPasswordHelper, $mailer);

        $data = new ForgotPassword();
        $data->email = 'unknown@example.com';

        $result = $processor->process($data, $this->createStub(\ApiPlatform\Metadata\Operation::class));

        $this->assertSame(
            'If an account exists with this email, a password reset link has been sent.',
            $result->message
        );
    }

    public function testKnownEmailSendsResetMail(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($user);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        $resetPasswordHelper = $this->createStub(ResetPasswordHelperInterface::class);
        $resetPasswordHelper->method('generateResetToken')
            ->willReturn(new ResetPasswordToken('token123', new \DateTimeImmutable('+1 hour'), time()));
        $resetPasswordHelper->method('getTokenLifetime')->willReturn(3600);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        $processor = $this->createProcessor($entityManager, $resetPasswordHelper, $mailer);

        $data = new ForgotPassword();
        $data->email = 'user@example.com';

        $result = $processor->process($data, $this->createStub(\ApiPlatform\Metadata\Operation::class));

        $this->assertSame(
            'If an account exists with this email, a password reset link has been sent.',
            $result->message
        );
    }

    public function testHelperExceptionReturnsGenericMessageWithoutSendingMail(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');

        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($user);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($repository);

        $resetPasswordHelper = $this->createStub(ResetPasswordHelperInterface::class);
        $resetPasswordHelper->method('generateResetToken')
            ->willThrowException(new ExpiredResetPasswordTokenException());

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())->method('send');

        $processor = $this->createProcessor($entityManager, $resetPasswordHelper, $mailer);

        $data = new ForgotPassword();
        $data->email = 'user@example.com';

        $result = $processor->process($data, $this->createStub(\ApiPlatform\Metadata\Operation::class));

        $this->assertSame(
            'If an account exists with this email, a password reset link has been sent.',
            $result->message
        );
    }
}
