<?php

namespace App\Tests\Unit\State;

use App\ApiResource\ResetPassword;
use App\Entity\User;
use App\State\ResetPasswordProcessor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordProcessorTest extends TestCase
{
    public function testValidTokenHashesPasswordAndReturnsSuccess(): void
    {
        $user = new User();
        $user->setEmail('user@example.com');

        $resetPasswordHelper = $this->createMock(ResetPasswordHelperInterface::class);
        $resetPasswordHelper->method('validateTokenAndFetchUser')->with('valid-token')->willReturn($user);
        $resetPasswordHelper->expects($this->once())->method('removeResetRequest')->with('valid-token');

        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')->willReturn('hashed-new-password');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $processor = new ResetPasswordProcessor($resetPasswordHelper, $passwordHasher, $entityManager);

        $data = new ResetPassword();
        $data->token = 'valid-token';
        $data->password = 'new-password';

        $result = $processor->process($data, $this->createStub(\ApiPlatform\Metadata\Operation::class));

        $this->assertTrue($result->success);
        $this->assertSame('hashed-new-password', $user->getPassword());
    }

    public function testInvalidTokenThrowsBadRequestException(): void
    {
        $resetPasswordHelper = $this->createStub(ResetPasswordHelperInterface::class);
        $resetPasswordHelper->method('validateTokenAndFetchUser')
            ->willThrowException(new ExpiredResetPasswordTokenException());

        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $processor = new ResetPasswordProcessor($resetPasswordHelper, $passwordHasher, $entityManager);

        $data = new ResetPassword();
        $data->token = 'expired-token';
        $data->password = 'new-password';

        $this->expectException(BadRequestHttpException::class);

        $processor->process($data, $this->createStub(\ApiPlatform\Metadata\Operation::class));
    }
}
