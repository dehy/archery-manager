<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Management;

use App\Entity\Club;
use App\Entity\Event;
use App\Repository\ClubRepository;
use App\Repository\EventRepository;
use App\Tests\application\LoggedInTestCase;
use Symfony\Component\HttpFoundation\Request;

final class EventManagementControllerTest extends LoggedInTestCase
{
    private const string URL_INDEX = '/events/manage';

    private const string URL_CREATE = '/events/manage/create';

    // ── Index ──────────────────────────────────────────────────────────

    public function testIndexRequiresClubAdmin(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(Request::METHOD_GET, self::URL_INDEX);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(Request::METHOD_GET, self::URL_INDEX);

        $this->assertResponseIsSuccessful();
    }

    public function testIndexRendersForClubAdmin(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $client->request(Request::METHOD_GET, self::URL_INDEX);

        $this->assertResponseIsSuccessful();
    }

    public function testIndexAdminSeesEventsFromAllClubs(): void
    {
        // Create client first (required before container access)
        $client = self::createLoggedInAsAdminClient();

        $client->request(Request::METHOD_GET, self::URL_INDEX);
        $this->assertResponseIsSuccessful();

        // Admin should have access to all events, not just their own club's
        $ladgEventId = $this->getFirstLadgEventId();
        $client->request(Request::METHOD_GET, \sprintf('/events/manage/%d/edit', $ladgEventId));
        $this->assertResponseIsSuccessful();
    }

    public function testIndexClubAdminOnlySeesOwnClubEvents(): void
    {
        // Create client first (required before container access)
        $client = self::createLoggedInAsClubAdminClient();

        /** @var ClubRepository $clubRepo */
        $clubRepo = self::getContainer()->get(ClubRepository::class);
        $ladgClub = $clubRepo->findOneBy(['name' => 'Les Archers de Guyenne']);
        $this->assertInstanceOf(Club::class, $ladgClub);

        /** @var EventRepository $eventRepo */
        $eventRepo = self::getContainer()->get(EventRepository::class);
        $ladgEventCount = \count($eventRepo->findBy(['club' => $ladgClub]));
        $totalEventCount = \count($eventRepo->findAll());

        // Pre-condition: fixtures must have events from at least 2 clubs
        $this->assertGreaterThan($ladgEventCount, $totalEventCount, 'Fixtures must include events from multiple clubs');

        $crawler = $client->request(Request::METHOD_GET, self::URL_INDEX);
        $this->assertResponseIsSuccessful();

        // The "total events" count shown on the page should match only LADG events
        $pageText = $crawler->text();
        $this->assertStringContainsString((string) $ladgEventCount, $pageText);
    }

    // ── Create (type selection) ────────────────────────────────────────

    public function testCreatePageRequiresClubAdmin(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(Request::METHOD_GET, self::URL_CREATE);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreatePageRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(Request::METHOD_GET, self::URL_CREATE);

        $this->assertResponseIsSuccessful();
    }

    // ── Create (type forms) ────────────────────────────────────────────

    public function testCreateTrainingFormShowsForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(Request::METHOD_GET, self::URL_CREATE.'/training');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCreateContestFormShowsForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(Request::METHOD_GET, self::URL_CREATE.'/contest');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCreateHobbyContestFormShowsForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(Request::METHOD_GET, self::URL_CREATE.'/hobby_contest');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCreateFreeTrainingFormShowsForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(Request::METHOD_GET, self::URL_CREATE.'/free_training');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCreateTrainingEventSubmit(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $crawler = $client->request(Request::METHOD_GET, self::URL_CREATE.'/training');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'training_event[name]' => 'Test Training Event',
            'training_event[discipline]' => 'indoor',
            'training_event[startsAt]' => '2027-01-15T10:00',
            'training_event[endsAt]' => '2027-01-15T12:00',
            'training_event[address]' => '1 rue de la Paix, Bordeaux',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testCreateContestEventSubmit(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $crawler = $client->request(Request::METHOD_GET, self::URL_CREATE.'/contest');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'contest_event[name]' => 'Test Contest Event',
            'contest_event[discipline]' => 'indoor',
            'contest_event[contestType]' => 'individual',
            'contest_event[startsAt]' => '2027-02-15T10:00',
            'contest_event[endsAt]' => '2027-02-15T12:00',
            'contest_event[address]' => '1 rue de la Paix, Bordeaux',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    // ── Edit ──────────────────────────────────────────────────────────

    public function testEditFormShowsForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $eventId = $this->getFirstLabdEventId();

        $client->request(Request::METHOD_GET, \sprintf('/events/manage/%d/edit', $eventId));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testEditFormShowsForClubAdminOwnClub(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $eventId = $this->getFirstLadgEventId();

        $client->request(Request::METHOD_GET, \sprintf('/events/manage/%d/edit', $eventId));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testClubAdminCannotEditOtherClubEvent(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $eventId = $this->getFirstLabdEventId(); // LADB event; club admin belongs to LADG

        $client->request(Request::METHOD_GET, \sprintf('/events/manage/%d/edit', $eventId));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanEditEventFromAnyClub(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $eventId = $this->getFirstLadgEventId(); // LADG event; admin's licensee is in LADB

        $client->request(Request::METHOD_GET, \sprintf('/events/manage/%d/edit', $eventId));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testEditFormSubmitSucceeds(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $eventId = $this->getFirstLabdEventId();

        $crawler = $client->request(Request::METHOD_GET, \sprintf('/events/manage/%d/edit', $eventId));
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form([
            'training_event[name]' => 'Updated Event Name',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    // ── Delete ────────────────────────────────────────────────────────

    public function testDeleteRequiresPost(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $eventId = $this->getFirstLabdEventId();

        $client->request(Request::METHOD_GET, \sprintf('/events/manage/%d/delete', $eventId));

        $this->assertResponseStatusCodeSame(405);
    }

    public function testDeleteWithValidToken(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // Load the index page to get a delete form with a valid CSRF token
        $crawler = $client->request(Request::METHOD_GET, self::URL_INDEX);
        $this->assertResponseIsSuccessful();

        // Submit the first delete form found on the page
        $deleteForm = $crawler->filter('.modal form[method="post"]')->first()->form();
        $client->submit($deleteForm);

        $this->assertResponseRedirects();
    }

    public function testDeleteWithInvalidToken(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $eventId = $this->getFirstLabdEventId();

        $client->request(Request::METHOD_POST, \sprintf('/events/manage/%d/delete', $eventId), [
            '_token' => 'invalid-token',
        ]);

        // Should redirect back without deleting (token mismatch is silently ignored)
        $this->assertResponseRedirects();
    }

    public function testClubAdminCannotDeleteOtherClubEvent(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $eventId = $this->getFirstLabdEventId(); // LADB event; club admin belongs to LADG

        $client->request(Request::METHOD_POST, \sprintf('/events/manage/%d/delete', $eventId), [
            '_token' => 'whatever',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    // ── Helper ────────────────────────────────────────────────────────

    private function getFirstLabdEventId(): int
    {
        /** @var ClubRepository $clubRepo */
        $clubRepo = self::getContainer()->get(ClubRepository::class);
        $club = $clubRepo->findOneBy(['name' => 'Les Archers du Bosquet']);
        $this->assertInstanceOf(Club::class, $club, 'Club LADB not found in fixtures');

        /** @var EventRepository $eventRepo */
        $eventRepo = self::getContainer()->get(EventRepository::class);
        $event = $eventRepo->findOneBy(['club' => $club]);
        $this->assertInstanceOf(Event::class, $event, 'No event found for club LADB');

        return $event->getId();
    }

    private function getFirstLadgEventId(): int
    {
        /** @var ClubRepository $clubRepo */
        $clubRepo = self::getContainer()->get(ClubRepository::class);
        $club = $clubRepo->findOneBy(['name' => 'Les Archers de Guyenne']);
        $this->assertInstanceOf(Club::class, $club, 'Club LADG not found in fixtures');

        /** @var EventRepository $eventRepo */
        $eventRepo = self::getContainer()->get(EventRepository::class);
        $event = $eventRepo->findOneBy(['club' => $club]);
        $this->assertInstanceOf(Event::class, $event, 'No event found for club LADG');

        return $event->getId();
    }
}
