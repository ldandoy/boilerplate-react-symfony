<?php

namespace App\Tests\Functional;

use Symfony\Component\Mime\Email;

class RegistrationTest extends ApiTestCase
{
    // --- POST /api/register ---

    public function testRegisterCreatesUnverifiedUserAndSendsEmail(): void
    {
        $this->client->request('POST', '/api/register', [
            'json' => ['email' => 'new@example.com', 'password' => self::PASSWORD],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'email' => 'new@example.com',
            'message' => 'User registered successfully',
        ]);

        $user = $this->findUser('new@example.com');
        $this->assertNotNull($user);
        $this->assertFalse($user->isVerified());
        $this->assertNotSame(self::PASSWORD, $user->getPassword());

        $this->assertEmailCount(1);
        $this->assertEmailAddressContains($this->getMailerMessage(), 'To', 'new@example.com');
    }

    public function testRegisterWithExistingEmailIsRejected(): void
    {
        $this->createUser('taken@example.com');

        $this->client->request('POST', '/api/register', [
            'json' => ['email' => 'taken@example.com', 'password' => self::PASSWORD],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertEmailCount(0);
    }

    public function testRegisterWithInvalidEmailIsRejected(): void
    {
        $this->client->request('POST', '/api/register', [
            'json' => ['email' => 'not-an-email', 'password' => self::PASSWORD],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertNull($this->findUser('not-an-email'));
    }

    public function testRegisterWithShortPasswordIsRejected(): void
    {
        $this->client->request('POST', '/api/register', [
            'json' => ['email' => 'new@example.com', 'password' => 'short'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertNull($this->findUser('new@example.com'));
    }

    public function testRegisterWithMissingFieldsIsRejected(): void
    {
        $this->client->request('POST', '/api/register', ['json' => []]);

        $this->assertResponseStatusCodeSame(422);
    }

    // --- POST /api/verify-email ---

    public function testVerifyEmailWithSignedUrlFromEmailVerifiesUser(): void
    {
        $this->client->request('POST', '/api/register', [
            'json' => ['email' => 'new@example.com', 'password' => self::PASSWORD],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $params = $this->extractVerificationParams();

        $this->client->request('POST', '/api/verify-email', ['json' => $params]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['verified' => true]);
        $this->assertTrue($this->findUser('new@example.com')->isVerified());
    }

    public function testVerifyEmailWithTamperedSignatureIsRejected(): void
    {
        $this->client->request('POST', '/api/register', [
            'json' => ['email' => 'new@example.com', 'password' => self::PASSWORD],
        ]);
        $params = $this->extractVerificationParams();
        $params['signedUrl'] = preg_replace('/signature=[^&]+/', 'signature=tampered', $params['signedUrl']);

        $this->client->request('POST', '/api/verify-email', ['json' => $params]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertFalse($this->findUser('new@example.com')->isVerified());
    }

    public function testVerifyEmailForAnotherUserIsRejected(): void
    {
        $other = $this->createUser('other@example.com', false);
        $this->client->request('POST', '/api/register', [
            'json' => ['email' => 'new@example.com', 'password' => self::PASSWORD],
        ]);
        $params = $this->extractVerificationParams();
        $params['userId'] = $other->getId();

        $this->client->request('POST', '/api/verify-email', ['json' => $params]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertFalse($this->findUser('other@example.com')->isVerified());
    }

    public function testVerifyEmailForUnknownUserIsNotFound(): void
    {
        $this->client->request('POST', '/api/verify-email', [
            'json' => ['userId' => 9999, 'signedUrl' => 'http://localhost/api/verify-email?signature=x'],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testVerifyEmailWithMissingFieldsIsRejected(): void
    {
        $this->client->request('POST', '/api/verify-email', ['json' => []]);

        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * @return array{userId: int, signedUrl: string}
     */
    private function extractVerificationParams(): array
    {
        $email = $this->getMailerMessage();
        $this->assertInstanceOf(Email::class, $email);
        $this->assertSame(1, preg_match('/href="([^"]+)"/', $email->getHtmlBody(), $matches));

        // Le lien envoyé pointe vers le front : ?userId=...&signedUrl=...
        parse_str((string) parse_url(html_entity_decode($matches[1]), PHP_URL_QUERY), $query);

        return ['userId' => (int) $query['userId'], 'signedUrl' => $query['signedUrl']];
    }
}
