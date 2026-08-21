<?php

namespace App\Tests\Unit\State;

use App\ApiResource\ForgotPassword;
use App\ApiResource\RegisterInput;
use App\ApiResource\RegisterOutput;
use App\Entity\User;
use App\Service\UserRegistrationService;
use App\State\UserProcessor;
use PHPUnit\Framework\TestCase;

class UserProcessorTest extends TestCase
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

    public function testThrowsOnUnsupportedDataType(): void
    {
        $registrationService = $this->createStub(UserRegistrationService::class);
        $processor = new UserProcessor($registrationService);

        $this->expectException(\InvalidArgumentException::class);

        $processor->process(new ForgotPassword(), $this->createStub(\ApiPlatform\Metadata\Operation::class));
    }

    public function testDelegatesToRegistrationServiceWithVerifyEmailRoute(): void
    {
        $input = new RegisterInput();
        $input->email = 'user@example.com';
        $input->password = 'plain-password';

        $user = $this->createUserWithId(42, 'user@example.com');

        $registrationService = $this->createMock(UserRegistrationService::class);
        $registrationService->expects($this->once())
            ->method('registerUser')
            ->with('user@example.com', 'plain-password', 'api_verify_email')
            ->willReturn($user);

        $processor = new UserProcessor($registrationService);
        $result = $processor->process($input, $this->createStub(\ApiPlatform\Metadata\Operation::class));

        $this->assertInstanceOf(RegisterOutput::class, $result);
    }

    public function testReturnsOutputWithoutPasswordField(): void
    {
        $input = new RegisterInput();
        $input->email = 'user@example.com';
        $input->password = 'plain-password';

        $user = $this->createUserWithId(7, 'user@example.com');

        $registrationService = $this->createStub(UserRegistrationService::class);
        $registrationService->method('registerUser')->willReturn($user);

        $processor = new UserProcessor($registrationService);
        $result = $processor->process($input, $this->createStub(\ApiPlatform\Metadata\Operation::class));

        $this->assertSame(7, $result->id);
        $this->assertSame('user@example.com', $result->email);
        $this->assertFalse(property_exists($result, 'password'));
    }
}
