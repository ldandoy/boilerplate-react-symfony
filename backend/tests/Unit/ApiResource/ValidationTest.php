<?php

namespace App\Tests\Unit\ApiResource;

use App\ApiResource\EmailVerification;
use App\ApiResource\ForgotPassword;
use App\ApiResource\RegisterInput;
use App\ApiResource\ResetPassword;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testRegisterInputValid(): void
    {
        $input = new RegisterInput();
        $input->email = 'user@example.com';
        $input->password = 'a-valid-password';

        $this->assertCount(0, $this->validator->validate($input));
    }

    public function testRegisterInputBlankFieldsAreInvalid(): void
    {
        $input = new RegisterInput();

        $this->assertGreaterThan(0, count($this->validator->validate($input)));
    }

    public function testRegisterInputMalformedEmailIsInvalid(): void
    {
        $input = new RegisterInput();
        $input->email = 'not-an-email';
        $input->password = 'a-valid-password';

        $this->assertGreaterThan(0, count($this->validator->validate($input)));
    }

    public function testRegisterInputPasswordTooShortIsInvalid(): void
    {
        $input = new RegisterInput();
        $input->email = 'user@example.com';
        $input->password = 'short1';

        $this->assertGreaterThan(0, count($this->validator->validate($input)));
    }

    public function testRegisterInputPasswordTooLongIsInvalid(): void
    {
        $input = new RegisterInput();
        $input->email = 'user@example.com';
        $input->password = str_repeat('a', 4097);

        $this->assertGreaterThan(0, count($this->validator->validate($input)));
    }

    public function testResetPasswordValid(): void
    {
        $data = new ResetPassword();
        $data->token = 'a-token';
        $data->password = 'a-valid-password';

        $this->assertCount(0, $this->validator->validate($data));
    }

    public function testResetPasswordBlankTokenIsInvalid(): void
    {
        $data = new ResetPassword();
        $data->password = 'a-valid-password';

        $this->assertGreaterThan(0, count($this->validator->validate($data)));
    }

    public function testResetPasswordShortPasswordIsInvalid(): void
    {
        $data = new ResetPassword();
        $data->token = 'a-token';
        $data->password = 'short1';

        $this->assertGreaterThan(0, count($this->validator->validate($data)));
    }

    public function testForgotPasswordValid(): void
    {
        $data = new ForgotPassword();
        $data->email = 'user@example.com';

        $this->assertCount(0, $this->validator->validate($data));
    }

    public function testForgotPasswordBlankEmailIsInvalid(): void
    {
        $data = new ForgotPassword();

        $this->assertGreaterThan(0, count($this->validator->validate($data)));
    }

    public function testForgotPasswordMalformedEmailIsInvalid(): void
    {
        $data = new ForgotPassword();
        $data->email = 'not-an-email';

        $this->assertGreaterThan(0, count($this->validator->validate($data)));
    }

    public function testEmailVerificationValid(): void
    {
        $data = new EmailVerification();
        $data->userId = 1;
        $data->signedUrl = 'http://example.com/verify?sig=abc';

        $this->assertCount(0, $this->validator->validate($data));
    }

    public function testEmailVerificationBlankFieldsAreInvalid(): void
    {
        $data = new EmailVerification();

        $this->assertGreaterThan(0, count($this->validator->validate($data)));
    }
}
