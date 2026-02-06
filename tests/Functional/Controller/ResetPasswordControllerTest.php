<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ResetPasswordControllerTest extends WebTestCase
{
    private const string URL_REQUEST = '/reset-password';

    private const string URL_CHECK_EMAIL = '/reset-password/check-email';

    // ── Request Password Reset ─────────────────────────────────────────

    public function testRequestRendersForm(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_REQUEST);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testRequestDoesNotRequireAuthentication(): void
    {
        $client = self::createClient();
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_REQUEST);

        $this->assertResponseIsSuccessful();
        // Should have email input field
        $this->assertGreaterThan(0, $crawler->filter('input[type="email"]')->count());
    }

    public function testRequestSubmitWithValidEmail(): void
    {
        $client = self::createClient();
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_REQUEST);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Envoyer un e-mail de réinitialisation du mot de passe')->form();
        $form['reset_password_request_form[email]'] = 'admin@acme.org';
        $client->submit($form);

        // Should redirect to check email page
        $this->assertResponseRedirects(self::URL_CHECK_EMAIL);
    }

    public function testRequestSubmitWithNonExistentEmail(): void
    {
        $client = self::createClient();
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_REQUEST);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Envoyer un e-mail de réinitialisation du mot de passe')->form();
        $form['reset_password_request_form[email]'] = 'nonexistent@example.com';
        $client->submit($form);

        // Should still redirect to check email (security: don't reveal if email exists)
        $this->assertResponseRedirects(self::URL_CHECK_EMAIL);
    }

    // ── Check Email ────────────────────────────────────────────────────

    public function testCheckEmailRendersWithoutToken(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_CHECK_EMAIL);

        $this->assertResponseIsSuccessful();
        // Should display check email message
        $this->assertSelectorExists('body');
    }

    public function testCheckEmailDoesNotRequireAuthentication(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_CHECK_EMAIL);

        $this->assertResponseIsSuccessful();
    }

    // ── Reset Password ─────────────────────────────────────────────────

    public function testResetWithInvalidTokenRedirects(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/reset-password/reset/invalid-token');

        // Should redirect (token stored in session then redirected)
        $this->assertResponseRedirects('/reset-password/reset');
    }

    public function testResetWithoutTokenReturnsError(): void
    {
        $client = self::createClient();

        // Directly access reset without token
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/reset-password/reset');

        // Should throw exception or show error
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isNotFound() || $response->isRedirection(),
            'Expected 404 or redirect when accessing reset without token'
        );
    }
}
