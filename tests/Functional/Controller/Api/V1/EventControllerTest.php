<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\Club;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\EventAttachment;
use App\Entity\EventParticipation;
use App\Entity\Group;
use App\Entity\TrainingEvent;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;

final class EventControllerTest extends ApiWebTestCase
{
    private const string EVENTS_URL = '/api/v1/events';

    private const string MONTH = '?from=2027-06-01&to=2027-06-30';

    private const string CALENDAR_URL = '/api/v1/calendar-subscription';

    private const string LADG = 'Les Archers de Guyenne';

    public function testItRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, self::EVENTS_URL);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $client->request(Request::METHOD_PUT, self::EVENTS_URL.'/1/participation');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $client->request(Request::METHOD_GET, self::CALENDAR_URL);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ── Calendar ───────────────────────────────────────────────────────

    public function testTheCalendarListsTheVisibleEventsOfThePeriodOldestFirst(): void
    {
        $client = self::createClient();
        $training = $this->training('Compétiteurs', '2027-06-12 18:00', ['group_competiteurs']);
        $contest = $this->contest('2027-06-02 09:00');
        $this->training('Autre club', '2027-06-05 18:00', [], $this->club('Les Archers du Bosquet'));
        $this->training('Hors période', '2027-07-20 18:00', ['group_competiteurs']);

        $this->get($client, self::EVENTS_URL.self::MONTH, $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'EventList');
        $body = $this->json($client);
        $this->assertSame('2027-06-01', $body['from']);
        $this->assertSame('2027-06-30', $body['to']);
        $this->assertSame([$contest->getId(), $training->getId()], array_column($body['events'], 'id'));
        $this->assertSame(['contest_official', 'training'], array_column($body['events'], 'type'));
        $this->assertNull($body['events'][0]['club']);
        $this->assertSame(self::LADG, $body['events'][1]['club']['name']);
    }

    public function testTheCalendarShowsTheDefaultAnswerAndWhetherTheMemberMayAnswer(): void
    {
        $client = self::createClient();
        $open = $this->training('Compétiteurs', '2027-06-12 18:00', ['group_competiteurs']);
        $restricted = $this->training('Loisirs', '2027-06-13 18:00', ['group_loisirs']);
        $contest = $this->contest('2027-06-16 09:00');

        $this->get($client, self::EVENTS_URL.self::MONTH, $this->tokenFor(self::MEMBER));

        $events = array_column($this->json($client)['events'], null, 'id');
        $this->assertSame('registered', $events[$open->getId()]['participation_state'], 'A training defaults to registered for its groups.');
        $this->assertTrue($events[$open->getId()]['can_participate']);
        $this->assertFalse($events[$restricted->getId()]['can_participate']);
        $this->assertNull($events[$restricted->getId()]['participation_state']);
        $this->assertNull($events[$contest->getId()]['participation_state'], 'A contest has no default.');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidRanges(): iterable
    {
        yield 'not a date' => ['?from=tomorrow'];
        yield 'impossible date' => ['?from=2027-02-30&to=2027-03-05'];
        yield 'to before from' => ['?from=2027-06-10&to=2027-06-01'];
        yield 'too long' => ['?from=2027-01-01&to=2027-12-31'];
        yield 'array' => ['?from[]=2027-06-01'];
    }

    #[DataProvider('invalidRanges')]
    public function testAnInvalidPeriodIsABadRequest(string $query): void
    {
        $client = self::createClient();

        $this->get($client, self::EVENTS_URL.$query, $this->tokenFor(self::MEMBER));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame('bad_request', $this->json($client)['error']);
    }

    public function testTheCalendarNeedsALicenseForTheSelectedSeason(): void
    {
        $client = self::createClient();

        $this->get($client, self::EVENTS_URL.self::MONTH, $this->tokenFor(self::MEMBER), ['HTTP_X_SEASON' => '2040']);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // ── Detail ─────────────────────────────────────────────────────────

    public function testATrainingIsDescribedWithTwoAnswersAndNoContestFields(): void
    {
        $client = self::createClient();
        $training = $this->training('Compétiteurs', '2027-06-12 18:00', ['group_competiteurs']);

        $this->get($client, $this->url($training), $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'EventResponse');
        $event = $this->json($client)['event'];
        $this->assertSame('training', $event['type']);
        $this->assertNull($event['contest_type']);
        $this->assertSame(['not_going', 'registered'], $event['participation_options']['states']);
        $this->assertNull($event['participation_options']['target_types']);
        $this->assertNull($event['participation_options']['departures']);
        $this->assertSame([['code' => 'CL', 'label' => 'Classique']], $event['participation_options']['activities']);
        $this->assertSame('registered', $event['my_participation']['participation_state']);
        $this->assertTrue($event['my_participation']['is_default']);
        $this->assertSame('CL', $event['my_participation']['activity']['code']);
        $this->assertSame(['Groupe Compétiteurs'], array_column($event['assigned_groups'], 'name'));
    }

    public function testAContestIsDescribedWithThreeAnswersATargetTypeAndDepartures(): void
    {
        $client = self::createClient();
        $contest = $this->contest('2027-06-16 09:00');

        $this->get($client, $this->url($contest), $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'EventResponse');
        $event = $this->json($client)['event'];
        $this->assertSame(['not_going', 'interested', 'registered'], $event['participation_options']['states']);
        $this->assertSame([1, 2, 3, 4], $event['participation_options']['departures']);
        $this->assertContains('trispot', array_column($event['participation_options']['target_types'], 'code'));
        $this->assertSame('individual', $event['contest_type']['code']);
        $this->assertNull($event['my_participation']['participation_state']);
    }

    public function testAnEventOfAnotherClubIsForbidden(): void
    {
        $client = self::createClient();
        $foreign = $this->training('Autre club', '2027-06-05 18:00', [], $this->club('Les Archers du Bosquet'));
        $token = $this->tokenFor(self::MEMBER);

        foreach (['', '/participants'] as $suffix) {
            $this->get($client, $this->url($foreign).$suffix, $token);
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        }

        $this->put($client, $this->url($foreign).'/participation', $token, ['participation_state' => 'registered']);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertSame('forbidden', $this->json($client)['error']);
    }

    public function testAnUnknownEventIsNotFound(): void
    {
        $client = self::createClient();

        $this->get($client, self::EVENTS_URL.'/99999999', $this->tokenFor(self::MEMBER));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ── Answering ──────────────────────────────────────────────────────

    public function testAMemberAnswersATrainingAndTheAnswerIsKept(): void
    {
        $client = self::createClient();
        $training = $this->training('Compétiteurs', '2027-06-12 18:00', ['group_competiteurs']);
        $token = $this->tokenFor(self::MEMBER);

        $this->put($client, $this->url($training).'/participation', $token, ['participation_state' => 'not_going']);

        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'EventResponse');
        $mine = $this->json($client)['event']['my_participation'];
        $this->assertSame('not_going', $mine['participation_state']);
        $this->assertFalse($mine['is_default']);

        $this->get($client, self::EVENTS_URL.self::MONTH, $token);
        $this->assertSame('not_going', $this->json($client)['events'][0]['participation_state']);
        $this->assertSame(1, $this->countAnswers($training));
    }

    public function testAnswerAgainReplacesTheAnswerWithoutDuplicatingIt(): void
    {
        $client = self::createClient();
        $contest = $this->contest('2027-06-16 09:00');
        $token = $this->tokenFor(self::MEMBER);

        $this->put($client, $this->url($contest).'/participation', $token, ['participation_state' => 'interested', 'target_type' => 'trispot', 'departure' => 2]);
        $this->assertResponseIsSuccessful();
        $this->put($client, $this->url($contest).'/participation', $token, ['participation_state' => 'registered', 'target_type' => 'monospot']);

        $this->assertResponseIsSuccessful();
        $mine = $this->json($client)['event']['my_participation'];
        $this->assertSame('registered', $mine['participation_state']);
        $this->assertSame('monospot', $mine['target_type']['code']);
        $this->assertNull($mine['departure'], 'The whole answer is replaced: an omitted departure is cleared.');
        $this->assertSame(1, $this->countAnswers($contest));
    }

    public function testAMemberCannotAnswerAnEventReservedForOtherGroups(): void
    {
        $client = self::createClient();
        $restricted = $this->training('Loisirs', '2027-06-13 18:00', ['group_loisirs']);
        $token = $this->tokenFor(self::MEMBER);

        $this->put($client, $this->url($restricted).'/participation', $token, ['participation_state' => 'registered']);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertResponseMatchesSchema($client, 'Error');
        $this->assertSame('event_restricted', $this->json($client)['error']);
        $this->assertSame(0, $this->countAnswers($restricted));

        $this->get($client, $this->url($restricted), $token);
        $this->assertResponseIsSuccessful();
        $this->assertFalse($this->json($client)['event']['can_participate'], 'The event stays visible, it just cannot be answered.');
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function invalidAnswers(): iterable
    {
        yield 'no state' => ['training', []];
        yield 'unknown state' => ['training', ['participation_state' => 'maybe']];
        yield 'interested is for contests' => ['training', ['participation_state' => 'interested']];
        yield 'state is not a string' => ['training', ['participation_state' => ['registered']]];
        yield 'activity not on the license' => ['training', ['participation_state' => 'registered', 'activity' => 'CO']];
        yield 'unknown activity' => ['training', ['participation_state' => 'registered', 'activity' => 'XX']];
        yield 'target type on a training' => ['training', ['participation_state' => 'registered', 'target_type' => 'trispot']];
        yield 'departure on a training' => ['training', ['participation_state' => 'registered', 'departure' => 1]];
        yield 'unknown target type' => ['contest', ['participation_state' => 'registered', 'target_type' => 'nope']];
        yield 'departure out of range' => ['contest', ['participation_state' => 'registered', 'departure' => 5]];
        yield 'departure as string' => ['contest', ['participation_state' => 'registered', 'departure' => '1']];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('invalidAnswers')]
    public function testAnAnswerBreakingTheRulesOfTheEventIsRefusedAndNothingIsRecorded(string $kind, array $body): void
    {
        $client = self::createClient();
        $event = 'contest' === $kind ? $this->contest('2027-06-16 09:00') : $this->training('Compétiteurs', '2027-06-12 18:00', ['group_competiteurs']);

        $this->put($client, $this->url($event).'/participation', $this->tokenFor(self::MEMBER), $body);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertResponseMatchesSchema($client, 'Error');
        $this->assertSame('validation_failed', $this->json($client)['error']);
        $this->assertSame(0, $this->countAnswers($event));
    }

    public function testTheBodyMustBeAJsonObject(): void
    {
        $client = self::createClient();
        $training = $this->training('Compétiteurs', '2027-06-12 18:00', ['group_competiteurs']);

        $client->request(Request::METHOD_PUT, $this->url($training).'/participation', server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->tokenFor(self::MEMBER),
            'HTTP_X_SEASON' => (string) self::FIXTURE_SEASON,
            'CONTENT_TYPE' => 'application/json',
        ], content: '{broken');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame('bad_request', $this->json($client)['error']);
    }

    // ── Participants ───────────────────────────────────────────────────

    public function testTheParticipantsOfAGroupTrainingIncludeTheMembersWithTheirDefaultAnswer(): void
    {
        $client = self::createClient();
        $training = $this->training('Compétiteurs', '2027-06-12 18:00', ['group_competiteurs']);
        $token = $this->tokenFor(self::MEMBER);
        $this->put($client, $this->url($training).'/participation', $token, ['participation_state' => 'not_going']);

        $this->get($client, $this->url($training).'/participants', $token);

        $this->assertResponseIsSuccessful();
        $participants = $this->json($client)['participants'];
        $mine = $this->licenseeOf(self::MEMBER)->getId();
        $this->assertGreaterThan(3, \count($participants));
        $this->assertSame($participants, array_values($participants));
        foreach ($participants as $participant) {
            $this->assertMatchesRegularExpression('/^\S+ \p{Lu}\.$/u', $participant['display_name'], 'A member sees "Firstname L.".');
            $this->assertSame($participant['licensee_id'] === $mine ? 'not_going' : 'registered', $participant['participation_state']);
            $this->assertSame($participant['licensee_id'] !== $mine, $participant['is_default']);
        }

        $this->assertSame(array_unique(array_column($participants, 'licensee_id')), array_column($participants, 'licensee_id'), 'Nobody is listed twice.');
        $this->assertResponseMatchesSchema($client, 'EventParticipantList');
    }

    public function testTheParticipantsOfAContestAreTheRecordedAnswers(): void
    {
        $client = self::createClient();
        $contest = $this->contest('2027-06-16 09:00');
        $this->put($client, $this->url($contest).'/participation', $this->tokenFor(self::MEMBER), ['participation_state' => 'registered', 'departure' => 1]);

        $this->get($client, $this->url($contest).'/participants', $this->tokenFor(self::OTHER_MEMBER));

        $this->assertResponseIsSuccessful();
        $participants = $this->json($client)['participants'];
        $this->assertCount(1, $participants);
        $this->assertSame(1, $participants[0]['departure']);
        $this->assertFalse($participants[0]['is_default']);
    }

    // ── Attachments ────────────────────────────────────────────────────

    public function testAnEventAttachmentIsListedAndStreamed(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $contest = $this->contest('2027-06-16 09:00');
        $attachment = $this->attach($contest, 'mandates/m.pdf', 'application/pdf', '%PDF-1.4 mandate');
        $token = $this->tokenFor(self::MEMBER);

        $this->get($client, $this->url($contest), $token);
        $listed = $this->json($client)['event']['attachments'];
        $this->assertCount(1, $listed);
        $this->assertSame($this->url($contest).'/attachments/'.$attachment->getId(), $listed[0]['url']);
        $this->assertSame('mandate', $listed[0]['type']['code']);
        $this->assertStringNotContainsString('mandates/m.pdf', (string) $client->getResponse()->getContent(), 'The storage key is not exposed.');

        $this->get($client, $listed[0]['url'], $token);
        $this->assertResponseIsSuccessful();
        $this->assertSame('%PDF-1.4 mandate', $client->getInternalResponse()->getContent());
        $this->assertResponseHeaderSame('content-type', 'application/pdf');
    }

    public function testAnAttachmentOfAnotherEventOrClubIsNotServed(): void
    {
        $client = self::createClient();
        $client->disableReboot();
        $contest = $this->contest('2027-06-16 09:00');
        $other = $this->contest('2027-06-17 09:00');
        $foreign = $this->training('Autre club', '2027-06-05 18:00', [], $this->club('Les Archers du Bosquet'));
        $mine = $this->attach($contest, 'mandates/a.pdf', 'application/pdf', 'a');
        $theirs = $this->attach($foreign, 'mandates/b.pdf', 'application/pdf', 'b');
        $token = $this->tokenFor(self::MEMBER);

        $this->get($client, $this->url($other).'/attachments/'.$mine->getId(), $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->get($client, $this->url($foreign).'/attachments/'.$theirs->getId(), $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // ── Calendar subscription ──────────────────────────────────────────

    public function testTheCalendarFeedUrlCanBeGeneratedRegeneratedAndRevoked(): void
    {
        $client = self::createClient();
        $token = $this->tokenFor(self::MEMBER);

        $this->get($client, self::CALENDAR_URL, $token);
        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'CalendarSubscriptionResponse');
        $this->assertNull($this->json($client)['subscription']['url']);

        $this->put($client, self::CALENDAR_URL, $token, []);
        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'CalendarSubscriptionResponse');
        $first = $this->json($client)['subscription']['url'];
        $this->assertMatchesRegularExpression('#^https?://[^/]+/calendar/[0-9a-f-]{36}\.ics$#', $first);

        $this->put($client, self::CALENDAR_URL, $token, []);
        $second = $this->json($client)['subscription']['url'];
        $this->assertNotSame($first, $second, 'Regenerating invalidates the previous URL.');

        $this->get($client, self::CALENDAR_URL, $token);
        $this->assertSame($second, $this->json($client)['subscription']['url']);

        $client->request(Request::METHOD_GET, $first);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $client->request(Request::METHOD_GET, $second);
        $this->assertResponseIsSuccessful();

        $this->delete($client, self::CALENDAR_URL, $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->delete($client, self::CALENDAR_URL, $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->get($client, self::CALENDAR_URL, $token);
        $this->assertNull($this->json($client)['subscription']['url']);
        $client->request(Request::METHOD_GET, $second);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testTheCalendarFeedOfOneMemberIsNotTheOneOfAnother(): void
    {
        $client = self::createClient();

        $this->put($client, self::CALENDAR_URL, $this->tokenFor(self::MEMBER), []);
        $mine = $this->json($client)['subscription']['url'];
        $this->get($client, self::CALENDAR_URL, $this->tokenFor(self::OTHER_MEMBER));

        $this->assertNull($this->json($client)['subscription']['url']);
        $this->assertNotNull($mine);
    }

    private function delete(KernelBrowser $client, string $url, string $token): void
    {
        $client->request(Request::METHOD_DELETE, $url, server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'HTTP_X_SEASON' => (string) self::FIXTURE_SEASON,
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function url(Event $event): string
    {
        return self::EVENTS_URL.'/'.$event->getId();
    }

    /**
     * @param array<string, mixed> $body
     */
    private function put(KernelBrowser $client, string $url, string $token, array $body): void
    {
        $client->request(Request::METHOD_PUT, $url, server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'HTTP_X_SEASON' => (string) self::FIXTURE_SEASON,
            'CONTENT_TYPE' => 'application/json',
        ], content: json_encode($body, \JSON_THROW_ON_ERROR));
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    private function club(string $name): Club
    {
        $club = $this->entityManager()->getRepository(Club::class)->findOneBy(['name' => $name]);
        $this->assertInstanceOf(Club::class, $club);

        return $club;
    }

    /**
     * @param list<string> $groups fixture keys: group_competiteurs, group_loisirs
     */
    private function training(string $name, string $startsAt, array $groups, ?Club $club = null): TrainingEvent
    {
        $event = new TrainingEvent();
        $this->fill($event, $name, $startsAt, 90, $club ?? $this->club(self::LADG));
        foreach ($groups as $key) {
            $event->addAssignedGroup($this->group('group_competiteurs' === $key ? 'Groupe Compétiteurs' : 'Groupe Loisir'));
        }

        return $event;
    }

    /**
     * A contest without club, like the federation contests of the fixtures.
     */
    private function contest(string $startsAt): ContestEvent
    {
        $event = new ContestEvent();
        $this->fill($event, 'Concours', $startsAt, 60 * 8, null);
        $event->setContestType('individual');

        return $event;
    }

    private function fill(Event $event, string $name, string $startsAt, int $minutes, ?Club $club): void
    {
        $start = new \DateTimeImmutable($startsAt);
        $event->setName($name)
            ->setDiscipline('indoor')
            ->setStartsAt($start)
            ->setEndsAt($start->modify(\sprintf('+%d minutes', $minutes)))
            ->setAddress('Gymnase')
            ->setAllDay(false);
        if ($club instanceof Club) {
            $event->setClub($club);
        }

        $event->setSlug(strtolower($name).'-'.uniqid());
        $this->entityManager()->persist($event);
        $this->entityManager()->flush();
    }

    private function group(string $name): Group
    {
        $group = $this->entityManager()->getRepository(Group::class)->findOneBy(['name' => $name, 'club' => $this->club(self::LADG)]);
        $this->assertInstanceOf(Group::class, $group);

        return $group;
    }

    private function countAnswers(Event $event): int
    {
        return $this->entityManager()->getRepository(EventParticipation::class)->count(['event' => $event]);
    }

    private function attach(Event $event, string $key, string $mimeType, string $content): EventAttachment
    {
        self::getContainer()->get('events_storage')->write($key, $content);
        $file = new EmbeddedFile();
        $file->setName($key);
        $file->setOriginalName(basename($key));
        $file->setMimeType($mimeType);
        $file->setSize(\strlen($content));

        $attachment = new EventAttachment();
        $attachment->setEvent($event)->setType('mandate')->setFile($file);
        $attachment->setUpdatedAt(new \DateTimeImmutable('2026-01-15 10:00:00'));
        $this->entityManager()->persist($attachment);
        $this->entityManager()->flush();

        return $attachment;
    }
}
