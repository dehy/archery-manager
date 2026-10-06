<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\UnicodeString;

final class ClubControllerTest extends ApiWebTestCase
{
    private const string CLUB_URL = '/api/v1/club';

    private const string MEMBERS_URL = '/api/v1/club/members';

    private const string LADG = 'Les Archers de Guyenne';

    public function testItRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, self::CLUB_URL);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $client->request(Request::METHOD_GET, self::MEMBERS_URL);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testTheClubPageDescribesTheClubOfTheSelectedSeason(): void
    {
        $client = self::createClient();

        $this->get($client, self::CLUB_URL, $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $body = $this->json($client);
        $this->assertSame(self::LADG, $body['club']['name']);
        $this->assertSame(
            ['id', 'name', 'city', 'contact_email', 'ffta_code', 'primary_color', 'logo_url'],
            array_keys($body['club']),
        );
        $this->assertGreaterThan(0, $body['member_count']);
        $this->assertNotEmpty($body['groups']);
        $this->assertSame(['id', 'name', 'description', 'member_count'], array_keys($body['groups'][0]));
        $this->assertIsInt($body['without_group_count']);
    }

    public function testTheClubPageDoesNotExposeTheFftaCredentials(): void
    {
        $client = self::createClient();

        $this->get($client, self::CLUB_URL, $this->tokenFor(self::MEMBER));

        $content = (string) $client->getResponse()->getContent();
        $this->assertStringNotContainsString('ffta_username', $content);
        $this->assertStringNotContainsString('ffta_password', $content);
    }

    public function testEveryClubScreenNeedsALicenseForTheSelectedSeason(): void
    {
        $client = self::createClient();

        foreach ([self::CLUB_URL, self::MEMBERS_URL] as $url) {
            $this->get($client, $url, $this->tokenFor(self::MEMBER), ['HTTP_X_SEASON' => '2019']);
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

            $this->get($client, $url, $this->tokenFor(self::APPLICANT));
            $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        }
    }

    public function testAMemberSeesTheOtherMembersAsFirstnameAndInitial(): void
    {
        $client = self::createClient();

        $this->get($client, self::MEMBERS_URL.'?per_page=100', $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $body = $this->json($client);
        $this->assertNotEmpty($body['data']);
        foreach ($body['data'] as $member) {
            $this->assertMatchesRegularExpression('/ \p{Lu}\.$/u', $member['display_name'], 'A member only sees the initial of the last name.');
        }
    }

    public function testACoachSeesFullNames(): void
    {
        $client = self::createClient();
        $licensee = $this->licenseeOf(self::OTHER_MEMBER);

        $this->get($client, self::MEMBERS_URL.'?per_page=100', $this->tokenFor(self::COACH));

        $names = array_column($this->json($client)['data'], 'display_name');
        $this->assertContains($licensee->getFullname(), $names);
    }

    public function testEachMemberCarriesWhatTheDirectoryCardShows(): void
    {
        $client = self::createClient();

        $this->get($client, self::MEMBERS_URL, $this->tokenFor(self::MEMBER));

        $member = $this->json($client)['data'][0];
        $this->assertSame(['id', 'display_name', 'groups', 'activities', 'picture_url'], array_keys($member));
        $this->assertIsList($member['groups']);
        foreach ($member['activities'] as $activity) {
            $this->assertSame(['code', 'label'], array_keys($activity));
        }

        $this->assertNull($member['picture_url'], 'Fixture members have no profile picture.');
    }

    public function testTheDirectoryDoesNotLeakPrivateFields(): void
    {
        $client = self::createClient();

        $this->get($client, self::MEMBERS_URL.'?per_page=100', $this->tokenFor(self::MEMBER));

        $content = (string) $client->getResponse()->getContent();
        foreach (['birthdate', 'email', 'phone', 'ffta_member_code', 'lastname'] as $field) {
            $this->assertStringNotContainsString($field, $content);
        }
    }

    public function testTheDirectoryIsPaginated(): void
    {
        $client = self::createClient();
        $token = $this->tokenFor(self::MEMBER);

        $this->get($client, self::MEMBERS_URL.'?per_page=5&page=1', $token);
        $first = $this->json($client);
        $this->get($client, self::MEMBERS_URL.'?per_page=5&page=2', $token);
        $second = $this->json($client);

        $this->assertCount(5, $first['data']);
        $this->assertSame(['page' => 1, 'per_page' => 5, 'total' => $first['meta']['total'], 'total_in_club' => $first['meta']['total_in_club']], $first['meta']);
        $this->assertGreaterThan(5, $first['meta']['total']);
        $this->assertSame(2, $second['meta']['page']);
        $this->assertSame([], array_intersect(array_column($first['data'], 'id'), array_column($second['data'], 'id')));
    }

    public function testTheDirectoryIsSortedByName(): void
    {
        $client = self::createClient();

        $this->get($client, self::MEMBERS_URL.'?per_page=100', $this->tokenFor(self::COACH));

        // Sorted ignoring case and accents, so "Émile" sorts with the E's.
        $names = array_map(
            static fn (string $name): string => new UnicodeString($name)->ascii()->lower()->toString(),
            array_column($this->json($client)['data'], 'display_name'),
        );
        $sorted = $names;
        sort($sorted, \SORT_STRING);
        $this->assertSame($sorted, $names);
    }

    public function testTheDirectoryCanBeFilteredByGroup(): void
    {
        $client = self::createClient();
        $token = $this->tokenFor(self::MEMBER);
        $this->get($client, self::CLUB_URL, $token);
        $group = null;
        foreach ($this->json($client)['groups'] as $candidate) {
            if ($candidate['member_count'] > 0) {
                $group = $candidate;
                break;
            }
        }

        $this->assertNotNull($group, 'The fixtures have a group with members.');
        $this->get($client, self::MEMBERS_URL.'?per_page=100&group='.$group['id'], $token);

        $body = $this->json($client);
        $this->assertSame($group['member_count'], $body['meta']['total']);
        foreach ($body['data'] as $member) {
            $this->assertContains($group['id'], array_column($member['groups'], 'id'));
        }
    }

    public function testTheDirectoryCanBeFilteredToMembersWithoutAGroup(): void
    {
        $client = self::createClient();
        $token = $this->tokenFor(self::MEMBER);

        $this->get($client, self::MEMBERS_URL.'?per_page=100&group=none', $token);

        $body = $this->json($client);
        $this->assertSame($body['filters']['without_group_count'], $body['meta']['total']);
        foreach ($body['data'] as $member) {
            $this->assertSame([], $member['groups']);
        }
    }

    public function testAGroupOfAnotherClubIsRejected(): void
    {
        $client = self::createClient();
        $this->get($client, self::CLUB_URL, $this->tokenFor(self::OTHER_CLUB_MEMBER));
        $otherClubGroup = $this->json($client)['groups'][0] ?? null;
        $this->assertNotNull($otherClubGroup, 'The other club has a group.');

        $this->get($client, self::MEMBERS_URL.'?group='.$otherClubGroup['id'], $this->tokenFor(self::MEMBER));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testTheDirectoryIsSearchedInTheDisplayedNameOnly(): void
    {
        $client = self::createClient();
        $token = $this->tokenFor(self::MEMBER);
        $target = $this->licenseeOf(self::OTHER_MEMBER);

        $this->get($client, self::MEMBERS_URL.'?per_page=100&q='.rawurlencode(mb_strtoupper((string) $target->getFirstname())), $token);
        $this->assertContains($target->getId(), array_column($this->json($client)['data'], 'id'), 'The search ignores case.');

        // The member only sees the initial of the last name: the full last name must not find anyone.
        $this->get($client, self::MEMBERS_URL.'?per_page=100&q='.rawurlencode((string) $target->getLastname()), $token);
        $this->assertNotContains($target->getId(), array_column($this->json($client)['data'], 'id'));
    }

    public function testACoachCanSearchByLastName(): void
    {
        $client = self::createClient();
        $target = $this->licenseeOf(self::OTHER_MEMBER);

        $this->get($client, self::MEMBERS_URL.'?per_page=100&q='.rawurlencode((string) $target->getLastname()), $this->tokenFor(self::COACH));

        $this->assertContains($target->getId(), array_column($this->json($client)['data'], 'id'));
    }

    #[DataProvider('invalidQueries')]
    public function testInvalidQueryParametersAreABadRequest(string $query): void
    {
        $client = self::createClient();

        $this->get($client, self::MEMBERS_URL.'?'.$query, $this->tokenFor(self::MEMBER));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame('bad_request', $this->json($client)['error']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidQueries(): iterable
    {
        yield 'page zero' => ['page=0'];
        yield 'negative page' => ['page=-1'];
        yield 'page not a number' => ['page=abc'];
        yield 'per_page zero' => ['per_page=0'];
        yield 'unknown group' => ['group=999999999'];
        yield 'group not a number' => ['group=abc'];
    }

    public function testThePageSizeIsCapped(): void
    {
        $client = self::createClient();

        $this->get($client, self::MEMBERS_URL.'?per_page=100000', $this->tokenFor(self::MEMBER));

        $this->assertSame(100, $this->json($client)['meta']['per_page']);
    }

    public function testAMemberOfAnotherClubSeesThatClubOnly(): void
    {
        $client = self::createClient();
        $token = $this->tokenFor(self::OTHER_CLUB_MEMBER);
        $ladgMember = $this->licenseeOf(self::MEMBER);

        $this->get($client, self::CLUB_URL, $token);
        $this->assertNotSame(self::LADG, $this->json($client)['club']['name']);

        $this->get($client, self::MEMBERS_URL.'?per_page=100', $token);
        $this->assertNotContains($ladgMember->getId(), array_column($this->json($client)['data'], 'id'));
    }
}
