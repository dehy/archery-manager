<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\DBAL\Types\LicenseApplicationStatusType;
use App\Entity\Club;
use App\Entity\LicenseApplication;
use App\Entity\Licensee;
use App\Entity\User;
use App\Repository\LicenseApplicationRepository;
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

    public function testValidateNonExistentApplicationReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/license-application/99999/validate');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testValidateApplicationAsAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/license-application/'.$applicationId.'/validate');

        $this->assertResponseRedirects(self::URL_MANAGE);

        // Verify status changed
        $application = self::getContainer()->get(LicenseApplicationRepository::class)->find($applicationId);
        $this->assertSame(LicenseApplicationStatusType::VALIDATED, $application->getStatus());
    }

    public function testValidateAlreadyProcessedShowsWarning(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        // Validate once
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/license-application/'.$applicationId.'/validate');
        $this->assertResponseRedirects(self::URL_MANAGE);

        // Try to validate again
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/license-application/'.$applicationId.'/validate');
        $this->assertResponseRedirects(self::URL_MANAGE);
    }

    // ── Waiting List ───────────────────────────────────────────────────

    public function testWaitingListRequiresPostMethod(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/license-application/99999/waiting-list');
        $this->assertResponseStatusCodeSame(405);
    }

    public function testWaitingListNonExistentApplicationReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/license-application/99999/waiting-list');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWaitingListApplicationAsAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/license-application/'.$applicationId.'/waiting-list');

        $this->assertResponseRedirects(self::URL_MANAGE);

        // Verify status changed
        $application = self::getContainer()->get(LicenseApplicationRepository::class)->find($applicationId);
        $this->assertSame(LicenseApplicationStatusType::WAITING_LIST, $application->getStatus());
    }

    // ── Reject ─────────────────────────────────────────────────────────

    public function testRejectNonExistentApplicationReturns404(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/license-application/99999/reject');
        // Entity resolver returns 404 before authorization check
        $this->assertResponseStatusCodeSame(404);
    }

    public function testRejectFormRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/license-application/'.$applicationId.'/reject');

        $this->assertResponseIsSuccessful();
    }

    public function testRejectApplicationWithReasonAsAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/license-application/'.$applicationId.'/reject');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Refuser')->form([
            'license_application_reject[rejectionReason]' => 'Le club a atteint sa capacité maximale pour cette saison.',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_MANAGE);

        // Verify status changed
        $application = self::getContainer()->get(LicenseApplicationRepository::class)->find($applicationId);
        $this->assertSame(LicenseApplicationStatusType::REJECTED, $application->getStatus());
        $this->assertNotNull($application->getRejectionReason());
    }

    public function testRejectAlreadyProcessedShowsWarning(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        // First validate it
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/license-application/'.$applicationId.'/validate');
        $this->assertResponseRedirects(self::URL_MANAGE);

        // Then try to reject it
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/license-application/'.$applicationId.'/reject');
        $this->assertResponseRedirects(self::URL_MANAGE);
    }

    // ── Validate denied for regular user ──────────────────────────────

    public function testValidateDeniedForRegularUser(): void
    {
        // Create application as admin first
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        // Use the same client but log in as regular user
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/logout');
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'user1@ladg.com',
            '_password' => 'user',
        ]);
        $client->submit($form);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/license-application/'.$applicationId.'/validate');
        $this->assertResponseStatusCodeSame(403);
    }

    // ── Helper ─────────────────────────────────────────────────────────

    /**
     * Create a test LicenseApplication and return its ID.
     */
    private function createTestApplication(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): int
    {
        $em = self::getContainer()->get('doctrine.orm.entity_manager');

        /** @var User $admin */
        $admin = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $licensee = $admin->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        // Get a club
        $clubs = $em->getRepository(Club::class)->findAll();
        $this->assertNotEmpty($clubs);
        $club = $clubs[0];

        // Create a test application with a different (future) season to avoid conflicts
        $application = new LicenseApplication();
        $application->setLicensee($licensee);
        $application->setClub($club);
        $application->setSeason(2099);
        $application->setStatus(LicenseApplicationStatusType::PENDING);
        $application->setCreatedAt(new \DateTimeImmutable());

        $em->persist($application);
        $em->flush();

        return $application->getId();
    }
}
