<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Repository\ContestEventRepository;
use App\Repository\EventRepository;
use App\Tests\application\LoggedInTestCase;

final class EventControllerTest extends LoggedInTestCase
{
    private const string URL_EVENTS = '/events';

    // ── Index (Calendar) ──────────────────────────────────────────────

    public function testIndexRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EVENTS);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testIndexRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EVENTS);

        $this->assertResponseIsSuccessful();
    }

    public function testIndexRendersForUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EVENTS);

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithMonthAndYearParams(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EVENTS.'?m=1&y=2026');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithDifferentMonth(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EVENTS.'?m=6&y=2025');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithDecemberMonth(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EVENTS.'?m=12&y=2025');

        $this->assertResponseIsSuccessful();
    }

    // ── Show ───────────────────────────────────────────────────────────

    public function testShowExistingEventAsAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $eventRepo = self::getContainer()->get(EventRepository::class);
        $events = $eventRepo->findAll();

        if (\count($events) > 0) {
            /** @var Event $event */
            $event = $events[0];
            $slug = $event->getSlug();

            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/events/'.$slug);
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('No events available for show test');
        }
    }

    public function testShowExistingEventAsUser(): void
    {
        $client = self::createLoggedInAsUserClient();

        $eventRepo = self::getContainer()->get(EventRepository::class);
        $events = $eventRepo->findAll();

        if (\count($events) > 0) {
            /** @var Event $event */
            $event = $events[0];
            $slug = $event->getSlug();

            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/events/'.$slug);
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('No events available for show test');
        }
    }

    public function testShowNonExistentEventReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/events/non-existent-slug-99999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowEventWithModalQueryParam(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $eventRepo = self::getContainer()->get(EventRepository::class);
        $events = $eventRepo->findAll();

        if (\count($events) > 0) {
            /** @var Event $event */
            $event = $events[0];
            $slug = $event->getSlug();

            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/events/'.$slug.'?modal=1');
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('No events available for modal test');
        }
    }

    // ── ICS Download ──────────────────────────────────────────────────

    public function testIcsDownloadForExistingEvent(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $eventRepo = self::getContainer()->get(EventRepository::class);
        $events = $eventRepo->findAll();

        if (\count($events) > 0) {
            /** @var Event $event */
            $event = $events[0];
            $slug = $event->getSlug();

            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/events/'.$slug.'.ics');
            $this->assertResponseIsSuccessful();

            $contentType = $client->getResponse()->headers->get('Content-Type');
            $this->assertStringContainsString('text/calendar', (string) $contentType);
        } else {
            $this->markTestSkipped('No events available for ICS test');
        }
    }

    public function testIcsDownloadNonExistentEventReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/events/non-existent-slug-99999.ics');

        $this->assertResponseStatusCodeSame(404);
    }

    // ── Attachment Download ───────────────────────────────────────────

    public function testAttachmentDownloadNonExistentReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/events/attachments/99999');

        $this->assertResponseStatusCodeSame(404);
    }

    // ── Results Show ──────────────────────────────────────────────────

    public function testResultsShowForExistingContestEvent(): void
    {
        $client = self::createLoggedInAsAdminClient();

        /** @var ContestEventRepository $contestEventRepo */
        $contestEventRepo = self::getContainer()->get(ContestEventRepository::class);
        $contests = $contestEventRepo->findAll();

        if (\count($contests) > 0) {
            /** @var ContestEvent $contest */
            $contest = $contests[0];
            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/events/'.$contest->getSlug().'/results');
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('No contest events available for results show test');
        }
    }

    public function testResultsShowNonExistentEventReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/events/non-existent-slug-99999/results');

        $this->assertResponseStatusCodeSame(404);
    }

    // ── Results Edit ──────────────────────────────────────────────────

    public function testResultsEditNonExistentEventReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/events/non-existent-slug-99999/results/edit');

        $this->assertResponseStatusCodeSame(404);
    }
}
