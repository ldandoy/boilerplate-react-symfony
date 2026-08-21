<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Security\EmailVerifier;
use App\Service\UserRegistrationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationServiceTest extends TestCase
{
    public function testRegisterUserHashesPasswordBeforePersisting(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $emailVerifier = $this->createStub(EmailVerifier::class);

        $passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'plain-password')
            ->willReturn('hashed-password');

        $service = new UserRegistrationService($entityManager, $passwordHasher, $emailVerifier);
        $user = $service->registerUser('user@example.com', 'plain-password', 'api_verify_email');

        $this->assertSame('hashed-password', $user->getPassword());
        $this->assertNotSame('plain-password', $user->getPassword());
    }

    public function testRegisterUserPersistsAndFlushesOnce(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')->willReturn('hashed-password');
        $emailVerifier = $this->createStub(EmailVerifier::class);

        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $entityManager->expects($this->once())->method('flush');

        $service = new UserRegistrationService($entityManager, $passwordHasher, $emailVerifier);
        $service->registerUser('user@example.com', 'plain-password', 'api_verify_email');
    }

    public function testRegisterUserSendsVerificationEmailWithGivenRoute(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')->willReturn('hashed-password');
        $emailVerifier = $this->createMock(EmailVerifier::class);

        $emailVerifier->expects($this->once())
            ->method('sendEmailConfirmation')
            ->with('api_verify_email', $this->isInstanceOf(User::class), $this->anything());

        $service = new UserRegistrationService($entityManager, $passwordHasher, $emailVerifier);
        $service->registerUser('user@example.com', 'plain-password', 'api_verify_email');
    }

    public function testRegisterUserReturnsUserWithCorrectEmail(): void
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $passwordHasher = $this->createStub(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')->willReturn('hashed-password');
        $emailVerifier = $this->createStub(EmailVerifier::class);

        $service = new UserRegistrationService($entityManager, $passwordHasher, $emailVerifier);
        $user = $service->registerUser('user@example.com', 'plain-password', 'api_verify_email');

        $this->assertSame('user@example.com', $user->getEmail());
    }
}
