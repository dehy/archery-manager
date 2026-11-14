<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Licensee;
use App\Entity\User;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
final class CalendarControllerTest extends LoggedInTestCase
{
    private const string URL_FEED = '/calendar/%s.ics';

    private const string URL_GENERATE = '/my-account/calendar/%s/generate-token';

    private const string URL_REVOKE = '/my-account/calendar/%s/revoke-token';

    // ── Helpers ────────────────────────────────────────────────────────

    private function getAdminLicensee(): Licensee
    {
        self::getContainer()->get(LicenseeRepository::class);
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        /** @var User $admin */
        $admin = $userRepository->findOneByEmail('admin@acme.org');
        $this->assertInstanceOf(User::class, $admin);
        $licensee = $admin->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        return $licensee;
    }

    /**
     * Submits the form of Mon compte that posts to $url, with its CSRF token.
     */
    private function submitFromAccountPage(KernelBrowser $client, string $url): void
    {
        $crawler = $client->request(Request::METHOD_GET, '/my-account');
        $client->submit($crawler->filter(\sprintf('form[action="%s"]', $url))->form());
    }

    // ── Feed ───────────────────────────────────────────────────────────

    public function testFeedReturns404ForUnknownToken(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, \sprintf(self::URL_FEED, '00000000-0000-0000-0000-000000000000'));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testFeedReturnsIcsForValidToken(): void
    {
        $client = self::createClient();

        $licensee = $this->getAdminLicensee();
        $licensee->generateCalendarToken();

        $token = $licensee->getCalendarToken();
        $this->assertNotNull($token);

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->flush();

        $client->request(Request::METHOD_GET, \sprintf(self::URL_FEED, $token));

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsStringIgnoringCase('text/calendar', (string) $client->getResponse()->headers->get('Content-Type'));
        $this->assertStringContainsString('BEGIN:VCALENDAR', (string) $client->getResponse()->getContent());
    }

    // ── Generate Token ─────────────────────────────────────────────────

    public function testGenerateTokenRequiresAuthentication(): void
    {
        $client = self::createClient();
        $licensee = $this->getAdminLicensee();
        $client->request(Request::METHOD_POST, \sprintf(self::URL_GENERATE, $licensee->getId()));

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testGenerateTokenCreatesTokenAndRedirects(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $licensee = $this->getAdminLicensee();

        $this->submitFromAccountPage($client, \sprintf(self::URL_GENERATE, $licensee->getId()));

        $this->assertResponseRedirects('/my-account');

        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = $client->getContainer()->get(LicenseeRepository::class);
        $reloaded = $licenseeRepository->find($licensee->getId());
        $this->assertInstanceOf(Licensee::class, $reloaded);
        $this->assertNotNull($reloaded->getCalendarToken());
    }

    public function testGenerateTokenDeniedForOtherUsersLicensee(): void
    {
        $client = self::createLoggedInAsUserClient();
        $licensee = $this->getAdminLicensee();

        $client->request(Request::METHOD_POST, \sprintf(self::URL_GENERATE, $licensee->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    // ── Revoke Token ───────────────────────────────────────────────────

    public function testRevokeTokenRequiresAuthentication(): void
    {
        $client = self::createClient();
        $licensee = $this->getAdminLicensee();
        $client->request(Request::METHOD_POST, \sprintf(self::URL_REVOKE, $licensee->getId()));

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testRevokeTokenRemovesToken(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $licensee = $this->getAdminLicensee();

        // Generate first
        $this->submitFromAccountPage($client, \sprintf(self::URL_GENERATE, $licensee->getId()));
        $this->assertResponseRedirects('/my-account');

        // Then revoke
        $this->submitFromAccountPage($client, \sprintf(self::URL_REVOKE, $licensee->getId()));
        $this->assertResponseRedirects('/my-account');

        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = $client->getContainer()->get(LicenseeRepository::class);
        $reloaded = $licenseeRepository->find($licensee->getId());
        $this->assertInstanceOf(Licensee::class, $reloaded);
        $this->assertNull($reloaded->getCalendarToken());
    }

    public function testRevokeTokenDeniedForOtherUsersLicensee(): void
    {
        $client = self::createLoggedInAsUserClient();
        $licensee = $this->getAdminLicensee();

        $client->request(Request::METHOD_POST, \sprintf(self::URL_REVOKE, $licensee->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    // ── CSRF ───────────────────────────────────────────────────────────

    public function testGenerateTokenRefusesAPostWithoutTheFormToken(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $licensee = $this->getAdminLicensee();
        $before = $licensee->getCalendarToken();

        // What a form on another site could send on behalf of the logged-in member
        $client->request(Request::METHOD_POST, \sprintf(self::URL_GENERATE, $licensee->getId()), ['_token' => 'forged']);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame($before, $this->reloadedCalendarToken($client, $licensee));
    }

    public function testRevokeTokenRefusesAPostWithoutTheFormToken(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $licensee = $this->getAdminLicensee();
        $this->submitFromAccountPage($client, \sprintf(self::URL_GENERATE, $licensee->getId()));
        $token = $this->reloadedCalendarToken($client, $licensee);
        $this->assertNotNull($token);

        $client->request(Request::METHOD_POST, \sprintf(self::URL_REVOKE, $licensee->getId()));

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame($token, $this->reloadedCalendarToken($client, $licensee));
    }

    private function reloadedCalendarToken(KernelBrowser $client, Licensee $licensee): ?string
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        $reloaded = $entityManager->find(Licensee::class, $licensee->getId());
        $this->assertInstanceOf(Licensee::class, $reloaded);

        return $reloaded->getCalendarToken();
    }
}
