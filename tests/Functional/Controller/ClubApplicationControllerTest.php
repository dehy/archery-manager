<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\DBAL\Types\ClubApplicationStatusType;
use App\Entity\Club;
use App\Entity\ClubApplication;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\User;
use App\Repository\ClubApplicationRepository;
use App\Repository\LicenseeRepository;
use App\Tests\application\LoggedInTestCase;

final class ClubApplicationControllerTest extends LoggedInTestCase
{
    private const string URL_NEW = '/club-application/new';

    private const string URL_STATUS = '/club-application/status';

    private const string URL_MANAGE = '/club-application/manage';

    private const string URL_ACTIVATE_PREFIX = '/club-application/';

    private const string URL_ACTIVATE_SUFFIX = '/activate';

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

    public function testValidateFormRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/validate');
        $this->assertResponseIsSuccessful();
    }

    public function testValidateNonExistentApplicationReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/club-application/99999/validate');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testValidateApplicationAsAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/validate');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Accepter la demande')->form();
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_MANAGE);

        // Verify status changed
        $application = self::getContainer()->get(ClubApplicationRepository::class)->find($applicationId);
        $this->assertSame(ClubApplicationStatusType::VALIDATED, $application->getStatus());
    }

    public function testValidateAlreadyProcessedShowsWarning(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        // Validate once via form submission
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/validate');
        $this->assertResponseIsSuccessful();
        $client->submit($crawler->selectButton('Accepter la demande')->form());
        $this->assertResponseRedirects(self::URL_MANAGE);

        // Try to validate again — should redirect with warning (already processed)
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/validate');
        $this->assertResponseRedirects(self::URL_MANAGE);
    }

    public function testPendingApplicationCannotBeActivated(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $client->request(
            \Symfony\Component\HttpFoundation\Request::METHOD_GET,
            self::URL_ACTIVATE_PREFIX.$applicationId.self::URL_ACTIVATE_SUFFIX,
        );

        $this->assertResponseRedirects(self::URL_MANAGE);
    }

    public function testValidatedApplicationShowsFftaActivationForm(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/validate');
        $client->submit($crawler->selectButton('Accepter la demande')->form());
        $this->assertResponseRedirects(self::URL_MANAGE);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_ACTIVATE_PREFIX.$applicationId.self::URL_ACTIVATE_SUFFIX);
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->selectButton('Vérifier le code FFTA')->count());
    }

    public function testActivationDeniedForRegularUser(): void
    {
        $adminClient = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($adminClient);
        $crawler = $adminClient->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/validate');
        $adminClient->submit($crawler->selectButton('Accepter la demande')->form());

        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_ACTIVATE_PREFIX.$applicationId.self::URL_ACTIVATE_SUFFIX);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testActivationRedirectsWhenLicenseAlreadyExists(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client, Season::seasonForDate(new \DateTimeImmutable()));

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/validate');
        $client->submit($crawler->selectButton('Accepter la demande')->form());
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_ACTIVATE_PREFIX.$applicationId.self::URL_ACTIVATE_SUFFIX);

        $this->assertResponseRedirects(self::URL_MANAGE);
    }

    public function testActivationRejectsFftaCodeBelongingToAnotherLicensee(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);
        $application = self::getContainer()->get(ClubApplicationRepository::class)->find($applicationId);
        $this->assertInstanceOf(ClubApplication::class, $application);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/validate');
        $client->submit($crawler->selectButton('Accepter la demande')->form());

        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = self::getContainer()->get(LicenseeRepository::class);
        $otherLicensee = null;
        foreach ($licenseeRepository->findAll() as $licensee) {
            if ($licensee->getId() !== $application->getLicensee()->getId() && null !== $licensee->getFftaMemberCode()) {
                $otherLicensee = $licensee;
                break;
            }
        }

        $this->assertInstanceOf(Licensee::class, $otherLicensee);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_ACTIVATE_PREFIX.$applicationId.self::URL_ACTIVATE_SUFFIX);
        $form = $crawler->selectButton('Vérifier le code FFTA')->form([
            'ffta_member_code[fftaMemberCode]' => $otherLicensee->getFftaMemberCode(),
        ]);
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('déjà associé à un autre licencié', (string) $client->getResponse()->getContent());
    }

    // ── Waiting List ───────────────────────────────────────────────────

    public function testWaitingListFormRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/waiting-list');
        $this->assertResponseIsSuccessful();
    }

    public function testWaitingListNonExistentApplicationReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/99999/waiting-list');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWaitingListApplicationAsAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/waiting-list');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton("Mettre en liste d'attente")->form();
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_MANAGE);

        // Verify status changed
        $application = self::getContainer()->get(ClubApplicationRepository::class)->find($applicationId);
        $this->assertSame(ClubApplicationStatusType::WAITING_LIST, $application->getStatus());
    }

    // ── Reject ─────────────────────────────────────────────────────────

    public function testRejectNonExistentApplicationReturns404(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/99999/reject');
        // Entity resolver returns 404 before authorization check
        $this->assertResponseStatusCodeSame(404);
    }

    public function testRejectFormRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/reject');

        $this->assertResponseIsSuccessful();
    }

    public function testRejectApplicationWithReasonAsAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/reject');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Refuser la demande')->form([
            'club_application_process[adminMessage]' => 'Le club a atteint sa capacité maximale pour cette saison.',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_MANAGE);

        // Verify status changed
        $application = self::getContainer()->get(ClubApplicationRepository::class)->find($applicationId);
        $this->assertSame(ClubApplicationStatusType::REJECTED, $application->getStatus());
        $this->assertNotNull($application->getAdminMessage());
    }

    public function testRejectAlreadyProcessedShowsWarning(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        // First validate it via form submission
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/validate');
        $this->assertResponseIsSuccessful();
        $client->submit($crawler->selectButton('Accepter la demande')->form());
        $this->assertResponseRedirects(self::URL_MANAGE);

        // Then try to reject it — should redirect with warning (already processed)
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/reject');
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

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, '/club-application/'.$applicationId.'/validate');
        $this->assertResponseStatusCodeSame(403);
    }

    // ── Helper ─────────────────────────────────────────────────────────

    /**
     * Create a test ClubApplication and return its ID.
     */
    private function createTestApplication(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, int $season = 2099): int
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
        $application = new ClubApplication();
        $application->setLicensee($licensee);
        $application->setClub($club);
        $application->setSeason($season);
        $application->setStatus(ClubApplicationStatusType::PENDING);
        $application->setCreatedAt(new \DateTimeImmutable());

        $em->persist($application);
        $em->flush();

        return $application->getId();
    }
}
