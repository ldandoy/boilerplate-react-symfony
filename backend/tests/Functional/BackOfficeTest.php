<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class BackOfficeTest extends ApiTestCase
{
    private function browser(): KernelBrowser
    {
        return $this->client->getKernelBrowser();
    }

    private function submitLogin(string $email): void
    {
        $browser = $this->browser();
        $browser->request('GET', '/login');
        // CSRF stateless : sans JS, Symfony valide le jeton via l'en-tête Origin
        $browser->submitForm('Sign in', [
            '_username' => $email,
            '_password' => self::PASSWORD,
        ], 'POST', ['HTTP_ORIGIN' => 'http://localhost']);
    }

    public function testHomeRedirectsToBackOffice(): void
    {
        $this->browser()->request('GET', '/');

        $this->assertResponseRedirects('/admin');
    }

    public function testBackOfficeRequiresLogin(): void
    {
        $this->browser()->request('GET', '/admin');

        $this->assertResponseRedirects('http://localhost/login');
    }

    public function testAdminLoginRedirectsToBackOffice(): void
    {
        $this->createUser('admin@example.com', roles: ['ROLE_ADMIN']);

        $this->submitLogin('admin@example.com');

        $this->assertResponseRedirects('/admin');
        $this->browser()->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testNonAdminCannotLogInToBackOffice(): void
    {
        $this->createUser();

        $this->submitLogin('user@example.com');

        $this->assertResponseRedirects('/login');
        $crawler = $this->browser()->followRedirect();
        $this->assertStringContainsString('Accès réservé aux administrateurs.', $crawler->filter('.alert-danger')->text());
    }

    public function testLoggedInAdminVisitingLoginIsRedirectedToBackOffice(): void
    {
        $this->browser()->loginUser($this->createUser('admin@example.com', roles: ['ROLE_ADMIN']));

        $this->browser()->request('GET', '/login');

        $this->assertResponseRedirects('/admin');
    }
}
