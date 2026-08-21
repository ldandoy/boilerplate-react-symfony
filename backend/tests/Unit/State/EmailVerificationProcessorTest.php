<?php

namespace App\Tests\Unit\State;

use App\ApiResource\EmailVerification;
use App\Entity\User;
use App\Repository\UserRepository;
use App\State\EmailVerificationProcessor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerificationProcessorTest extends TestCase
{
    private function createUserWithId(int $id, string $email): User
    {
        $user = new User();
        $user->setEmail($email);

        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($user, $id);

        return $user;
    }

    public function testUnknownUserThrowsNotFoundException(): void
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('find')->willReturn(null);

        $verifyEmailHelper = $this->createStub(VerifyEmailHelperInterface::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $processor = new EmailVerificationProcessor($userRepository, $verifyEmailHelper, $entityManager);

        $data = new EmailVerification();
        $data->userId = 99;
        $data->signedUrl = 'http://example.com/verify';

        $this->expectException(NotFoundHttpException::class);

        $processor->process($data, $this->createStub(\ApiPlatform\Metadata\Operation::class));
    }

    public function testValidSignatureMarksUserVerified(): void
    {
        $user = $this->createUserWithId(5, 'user@example.com');

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('find')->willReturn($user);

        $verifyEmailHelper = $this->createMock(VerifyEmailHelperInterface::class);
        $verifyEmailHelper->expects($this->once())
            ->method('validateEmailConfirmation')
            ->with('signed-url', '5', 'user@example.com');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $processor = new EmailVerificationProcessor($userRepository, $verifyEmailHelper, $entityManager);

        $data = new EmailVerification();
        $data->userId = 5;
        $data->signedUrl = 'signed-url';

        $result = $processor->process($data, $this->createStub(\ApiPlatform\Metadata\Operation::class));

        $this->assertTrue($user->isVerified());
        $this->assertTrue($result->verified);
        $this->assertSame(5, $result->userId);
    }

    public function testInvalidSignatureThrowsBadRequestException(): void
    {
        $user = $this->createUserWithId(5, 'user@example.com');

        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('find')->willReturn($user);

        $verifyEmailHelper = $this->createStub(VerifyEmailHelperInterface::class);
        $verifyEmailHelper->method('validateEmailConfirmation')
            ->willThrowException(new ExpiredSignatureException());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $processor = new EmailVerificationProcessor($userRepository, $verifyEmailHelper, $entityManager);

        $data = new EmailVerification();
        $data->userId = 5;
        $data->signedUrl = 'bad-signed-url';

        $this->expectException(BadRequestHttpException::class);

        $processor->process($data, $this->createStub(\ApiPlatform\Metadata\Operation::class));
    }
}
