<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\DBAL\Types\ClubApplicationStatusType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class HomeControllerTest extends ApiWebTestCase
{
    private const string URL = '/api/v1/home';

    public function testItRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, self::URL);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAMemberWithALicenseGetsTheDashboard(): void
    {
        $client = self::createClient();

        $this->get($client, self::URL, $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('cache-control', 'no-store, private');
        $body = $this->json($client);
        $licensee = $this->licenseeOf(self::MEMBER);
        $this->assertSame('dashboard', $body['state']);
        $this->assertSame($licensee->getId(), $body['licensee']['id']);
        $this->assertSame($licensee->getFirstname(), $body['licensee']['firstname']);
        $this->assertSame($licensee->getFftaMemberCode(), $body['licensee']['ffta_member_code']);
        $this->assertIsList($body['next_events']);
        $this->assertLessThanOrEqual(5, \count($body['next_events']));
        $this->assertIsList($body['last_results']);
    }

    public function testTheUpcomingEventsCarryWhatTheDashboardNeeds(): void
    {
        $client = self::createClient();

        $this->get($client, self::URL, $this->tokenFor(self::COACH));

        $events = $this->json($client)['next_events'];
        $this->assertNotEmpty($events, 'The fixtures contain upcoming events for the club.');
        $event = $events[0];
        $this->assertSame(
            ['id', 'slug', 'title', 'type', 'starts_at', 'ends_at', 'all_day', 'address', 'participation_state', 'participants_count'],
            array_keys($event),
        );
        $this->assertContains($event['type'], ['contest_official', 'contest_hobby', 'training', 'free_training', 'other']);
        $this->assertNotFalse(\DateTimeImmutable::createFromFormat(\DATE_ATOM, $event['starts_at']));
        $this->assertIsInt($event['participants_count']);
    }

    public function testALicenseeWithoutALicenseForTheSeasonSeesItsApplications(): void
    {
        $client = self::createClient();

        $this->get($client, self::URL, $this->tokenFor(self::APPLICANT));

        $this->assertResponseIsSuccessful();
        $body = $this->json($client);
        $this->assertSame('no_license', $body['state']);
        $this->assertArrayNotHasKey('next_events', $body);
        $this->assertCount(1, $body['applications']);
        $application = $body['applications'][0];
        $this->assertSame(ClubApplicationStatusType::PENDING, $application['status']);
        $this->assertSame('Les Archers de Guyenne', $application['club']['name']);
        $this->assertSame($body['season'], $application['season']);
    }

    public function testASeasonWithoutLicenseTurnsAMemberIntoANoLicenseState(): void
    {
        $client = self::createClient();

        $this->get($client, self::URL, $this->tokenFor(self::MEMBER), ['HTTP_X_SEASON' => '2019']);

        $body = $this->json($client);
        $this->assertSame('no_license', $body['state']);
        $this->assertSame(2019, $body['season']);
        $this->assertSame([], $body['applications']);
    }

    public function testAnAccountWithoutLicenseeIsABlankAccount(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = new User()
            ->setEmail('blank@example.com')
            ->setFirstname('Blank')
            ->setLastname('Account')
            ->setGender('F')
            ->setBirthdate(new \DateTimeImmutable('1990-01-01'))
            ->setRoles(['ROLE_USER'])
            ->setPassword('!!');
        $entityManager->persist($user);
        $entityManager->flush();

        $this->get($client, self::URL, $this->tokenFor('blank@example.com'));

        $this->assertResponseIsSuccessful();
        $this->assertSame(['state' => 'blank_account', 'season' => $this->json($client)['season'], 'licensee' => null], $this->json($client));
    }

    public function testTheLicenseeHeaderChoosesWhoTheDashboardIsFor(): void
    {
        $client = self::createClient();
        $licensee = $this->licenseeOf(self::MEMBER);

        $this->get($client, self::URL, $this->tokenFor(self::MEMBER), ['HTTP_X_LICENSEE' => (string) $licensee->getFftaMemberCode()]);
        $this->assertSame($licensee->getId(), $this->json($client)['licensee']['id']);

        $this->get($client, self::URL, $this->tokenFor(self::MEMBER), ['HTTP_X_LICENSEE' => (string) $this->licenseeOf(self::OTHER_MEMBER)->getFftaMemberCode()]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
