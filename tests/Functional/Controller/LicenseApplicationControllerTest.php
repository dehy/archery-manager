<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\application\LoggedInTestCase;

final class LicenseApplicationControllerTest extends LoggedInTestCase
{
    private const string URL_NEW = '/license-application/new';

    private const string URL_STATUS = '/license-application/status';

    private const string URL_MANAGE = '/license-application/manage';

    // ── New Application ────────────────────────────────────────────────

    public function testNewApplicationRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_NEW);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testNewApplicationRendersForUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_NEW);

        // User has a license for the current season, so should be redirected with info flash
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirection(),
            'Expected success or redirect response'
        );
    }

    public function testNewApplicationRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_NEW);

        // Admin has a license for the current season, so may be redirected
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirection(),
            'Expected success or redirect response'
        );
    }

    // ── Status ─────────────────────────────────────────────────────────

    public function testStatusRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_STATUS);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testStatusRendersForUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_STATUS);

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirection(),
            'Expected success or redirect response'
        );
    }

    public function testStatusRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_STATUS);

        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirection(),
            'Expected success or redirect response'
        );
    }

    // ── Manage ─────────────────────────────────────────────────────────

    public function testManageRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANAGE);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testManageDeniedForRegularUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANAGE);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testManageRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANAGE);

        // Admin should be able to see manage page
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirection(),
            'Expected success or redirect response'
        );
    }

    // ── Validate ───────────────────────────────────────────────────────

    public function testValidateRequiresPostMethod(): void
    {
        $client = self::createLoggedInAsAdminClient();
        // GET should not be allowed for validate action
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/license-application/99999/validate');
        $this->assertResponseStatusCodeSame(405);
    }

    // ── Waiting List ───────────────────────────────────────────────────

    public function testWaitingListRequiresPostMethod(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/license-application/99999/waiting-list');
        $this->assertResponseStatusCodeSame(405);
    }

    // ── Reject ─────────────────────────────────────────────────────────

    public function testRejectNonExistentApplicationReturns404(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/license-application/99999/reject');
        // Entity resolver returns 404 before authorization check
        $this->assertResponseStatusCodeSame(404);
    }
}
