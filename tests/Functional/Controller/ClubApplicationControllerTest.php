<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\DBAL\Types\ClubApplicationStatusType;
use App\Entity\Club;
use App\Entity\ClubApplication;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\User;
use App\Helper\FftaHelper;
use App\Repository\ClubRepository;
use App\Repository\ClubApplicationRepository;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use App\Scrapper\FftaProfile;
use App\Scrapper\FftaScrapper;
use App\Tests\application\LoggedInTestCase;

final class ClubApplicationControllerTest extends LoggedInTestCase
{
    private const string URL_NEW = '/club-application/new';

    private const string URL_STATUS = '/club-application/status';

    private const string URL_MANAGE = '/club-application/manage';

    private const string URL_ACTIVATE_PREFIX = '/club-application/';

    private const string URL_ACTIVATE_SUFFIX = '/activate';

    private const string URL_WAITING_LIST_SUFFIX = '/waiting-list';

    private const string WAITING_LIST_BUTTON = "Mettre en liste d'attente";

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

    public function testClosedClubAutomaticallyPlacesNewApplicationOnWaitingList(): void
    {
        $client = self::createClient();
        $userRepository = self::getContainer()->get(UserRepository::class);
        $applicant = $userRepository->findOneByEmail('applicant5@ladg.com');
        $this->assertInstanceOf(User::class, $applicant);

        $club = self::getContainer()->get(ClubRepository::class)->findOneBy(['name' => 'Les Archers du Bosquet']);
        $this->assertInstanceOf(Club::class, $club);
        $club->setAcceptingApplications(false);
        self::getContainer()->get('doctrine.orm.entity_manager')->flush();

        $client->loginUser($applicant);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_NEW);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Envoyer la demande')->form([
            'club_application[club]' => (string) $club->getId(),
        ]);
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_STATUS);
        $applications = self::getContainer()->get(ClubApplicationRepository::class)->findBy([
            'licensee' => $applicant->getLicensees()->first(),
            'club' => $club,
            'season' => Season::seasonForDate(new \DateTimeImmutable()),
        ]);
        $this->assertCount(1, $applications);
        $this->assertSame(ClubApplicationStatusType::WAITING_LIST, $applications[0]->getStatus());
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
        $client = self::createLoggedInAsClubAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANAGE);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#pending');
        $this->assertSelectorExists('#waiting-list');
        $this->assertSelectorExists('#validated');
        $this->assertSelectorExists('#closed');
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

        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail('user1@ladg.com');
        $this->assertInstanceOf(User::class, $user);
        $adminClient->loginUser($user);
        $adminClient->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_ACTIVATE_PREFIX.$applicationId.self::URL_ACTIVATE_SUFFIX);

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

    public function testActivationRejectsInvalidFftaCodeFormat(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/validate');
        $client->submit($crawler->selectButton('Accepter la demande')->form());

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_ACTIVATE_PREFIX.$applicationId.self::URL_ACTIVATE_SUFFIX, [
            'ffta_member_code' => ['fftaMemberCode' => 'abc'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('7 ou 8 caractères alphanumériques', (string) $client->getResponse()->getContent());
    }

    public function testActivationCreatesLicenseAfterFftaConfirmation(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->disableReboot();

        $applicationId = $this->createTestApplication($client);

        $application = self::getContainer()->get(ClubApplicationRepository::class)->find($applicationId);
        $this->assertInstanceOf(ClubApplication::class, $application);
        $licensee = $application->getLicensee();
        $this->assertInstanceOf(Licensee::class, $licensee);
        $fftaCode = $licensee->getFftaMemberCode() ?? 'Z1234567';
        $fftaId = $licensee->getFftaId() ?? 987654;

        $profile = new FftaProfile();
        $profile->setId($fftaId);
        $profile->setCodeAdherent($fftaCode);
        $profile->setNom($licensee->getLastname());
        $profile->setPrenom($licensee->getFirstname());
        $profile->setDateNaissance(\DateTime::createFromInterface($licensee->getBirthdate()));

        $scrapper = $this->createMock(FftaScrapper::class);
        $scrapper->method('findLicenseeIdFromCode')->willReturn($fftaId);
        $scrapper->method('fetchLicenseeProfile')->willReturn($profile);
        $fftaHelper = $this->createMock(FftaHelper::class);
        $fftaHelper->method('getScrapper')->willReturn($scrapper);
        // Must be replaced before the first request instantiates the real helper
        self::getContainer()->set(FftaHelper::class, $fftaHelper);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/validate');
        $client->submit($crawler->selectButton('Accepter la demande')->form());

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_ACTIVATE_PREFIX.$applicationId.self::URL_ACTIVATE_SUFFIX);
        $crawler = $client->submit($crawler->selectButton('Vérifier le code FFTA')->form([
            'ffta_member_code[fftaMemberCode]' => $fftaCode,
        ]));
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-success', 'Licence FFTA trouvée');

        $licenseForm = $crawler->selectButton('Activer la licence')->form();
        $licenseForm['license_form[activities]'][0]->tick();
        $client->submit($licenseForm);
        $this->assertResponseRedirects(self::URL_MANAGE);

        $licensee = self::getContainer()->get(LicenseeRepository::class)->find($licensee->getId());
        $license = $licensee->getLicenseForSeason(2099);
        $this->assertInstanceOf(License::class, $license);
        $this->assertSame($application->getClub()->getId(), $license->getClub()->getId());
        $this->assertSame($fftaId, $licensee->getFftaId());
        $this->assertSame(strtoupper($fftaCode), $licensee->getFftaMemberCode());
    }

    // ── Waiting List ───────────────────────────────────────────────────

    public function testWaitingListFormRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_ACTIVATE_PREFIX.$applicationId.self::URL_WAITING_LIST_SUFFIX);
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

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_ACTIVATE_PREFIX.$applicationId.self::URL_WAITING_LIST_SUFFIX);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton(self::WAITING_LIST_BUTTON)->form();
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_MANAGE);

        // Verify status changed
        $application = self::getContainer()->get(ClubApplicationRepository::class)->find($applicationId);
        $this->assertSame(ClubApplicationStatusType::WAITING_LIST, $application->getStatus());
    }

    public function testWaitlistedApplicationCanBeValidated(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_ACTIVATE_PREFIX.$applicationId.self::URL_WAITING_LIST_SUFFIX);
        $client->submit($crawler->selectButton(self::WAITING_LIST_BUTTON)->form());

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/validate');
        $this->assertResponseIsSuccessful();
        $client->submit($crawler->selectButton('Accepter la demande')->form());

        $this->assertResponseRedirects(self::URL_MANAGE);
        $application = self::getContainer()->get(ClubApplicationRepository::class)->find($applicationId);
        $this->assertSame(ClubApplicationStatusType::VALIDATED, $application->getStatus());
    }

    public function testWaitlistedApplicationCanBeRejected(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $applicationId = $this->createTestApplication($client);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_ACTIVATE_PREFIX.$applicationId.self::URL_WAITING_LIST_SUFFIX);
        $client->submit($crawler->selectButton(self::WAITING_LIST_BUTTON)->form());

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/club-application/'.$applicationId.'/reject');
        $this->assertResponseIsSuccessful();
        $client->submit($crawler->selectButton('Refuser la demande')->form());

        $this->assertResponseRedirects(self::URL_MANAGE);
        $application = self::getContainer()->get(ClubApplicationRepository::class)->find($applicationId);
        $this->assertSame(ClubApplicationStatusType::REJECTED, $application->getStatus());
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
