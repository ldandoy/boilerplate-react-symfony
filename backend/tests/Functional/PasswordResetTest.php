<?php

namespace App\Tests\Functional;

use Symfony\Component\Mime\Email;

class PasswordResetTest extends ApiTestCase
{
    // --- POST /api/forgot-password ---

    public function testForgotPasswordSendsResetEmail(): void
    {
        $this->createUser();

        $this->client->request('POST', '/api/forgot-password', [
            'json' => ['email' => 'user@example.com'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertEmailCount(1);
        $this->assertEmailAddressContains($this->getMailerMessage(), 'To', 'user@example.com');
    }

    public function testForgotPasswordForUnknownEmailDoesNotLeakExistence(): void
    {
        $this->createUser();

        $this->client->request('POST', '/api/forgot-password', ['json' => ['email' => 'user@example.com']]);
        $known = $this->client->getResponse()->toArray();

        $this->client->request('POST', '/api/forgot-password', ['json' => ['email' => 'nobody@example.com']]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame($known['message'], $this->client->getResponse()->toArray()['message']);
        $this->assertEmailCount(0);
    }

    public function testForgotPasswordWithInvalidEmailIsRejected(): void
    {
        $this->client->request('POST', '/api/forgot-password', ['json' => ['email' => 'not-an-email']]);

        $this->assertResponseStatusCodeSame(422);
    }

    // --- POST /api/reset-password ---

    public function testResetPasswordWithTokenFromEmailChangesPassword(): void
    {
        $this->createUser();
        $token = $this->requestResetToken();

        $this->client->request('POST', '/api/reset-password', [
            'json' => ['token' => $token, 'password' => 'my-new-password'],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['success' => true]);

        $this->login('user@example.com', 'my-new-password');
        $this->client->request('POST', '/api/login_check', [
            'json' => ['email' => 'user@example.com', 'password' => self::PASSWORD],
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testResetPasswordTokenCannotBeReused(): void
    {
        $this->createUser();
        $token = $this->requestResetToken();

        $this->client->request('POST', '/api/reset-password', [
            'json' => ['token' => $token, 'password' => 'my-new-password'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/api/reset-password', [
            'json' => ['token' => $token, 'password' => 'another-password'],
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testResetPasswordWithInvalidTokenIsRejected(): void
    {
        $this->client->request('POST', '/api/reset-password', [
            'json' => ['token' => 'invalid-token', 'password' => 'my-new-password'],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testResetPasswordWithShortPasswordIsRejected(): void
    {
        $this->createUser();
        $token = $this->requestResetToken();

        $this->client->request('POST', '/api/reset-password', [
            'json' => ['token' => $token, 'password' => 'short'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->login();
    }

    private function requestResetToken(): string
    {
        $this->client->request('POST', '/api/forgot-password', ['json' => ['email' => 'user@example.com']]);
        $this->assertResponseStatusCodeSame(201);

        $email = $this->getMailerMessage();
        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame(1, preg_match('/reset-password\?token=([A-Za-z0-9]+)/', $email->getHtmlBody(), $matches));

        return $matches[1];
    }
}
