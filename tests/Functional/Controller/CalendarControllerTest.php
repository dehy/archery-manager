<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
final class CalendarControllerTest extends LoggedInTestCase
{
    private const string URL_FEED = '/calendar/%s.ics';

    private const string URL_GENERATE = '/my-account/calendar/generate-token';

    private const string URL_REVOKE = '/my-account/calendar/revoke-token';

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

        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        /** @var User $user */
        $user = $userRepository->findOneByEmail('admin@acme.org');
        $this->assertInstanceOf(User::class, $user);

        $user->generateCalendarToken();
        $token = $user->getCalendarToken();
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
        $client->request(Request::METHOD_POST, self::URL_GENERATE);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testGenerateTokenCreatesTokenAndRedirects(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(Request::METHOD_POST, self::URL_GENERATE);

        $this->assertResponseRedirects('/my-account');

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $this->assertNotNull($user->getCalendarToken());
    }

    // ── Revoke Token ───────────────────────────────────────────────────

    public function testRevokeTokenRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_POST, self::URL_REVOKE);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testRevokeTokenRemovesToken(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // First generate a token
        $client->request(Request::METHOD_POST, self::URL_GENERATE);
        $this->assertResponseRedirects('/my-account');

        /** @var User $currentUser */
        $currentUser = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertNotNull($currentUser->getCalendarToken());
        $userId = $currentUser->getId();

        // Then revoke it
        $client->request(Request::METHOD_POST, self::URL_REVOKE);
        $this->assertResponseRedirects('/my-account');

        // Reload user from repository to check token is null
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);
        $reloadedUser = $userRepository->find($userId);
        $this->assertInstanceOf(User::class, $reloadedUser);
        $this->assertNull($reloadedUser->getCalendarToken());
    }
}
