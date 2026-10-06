<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Api\V1\ClubPresenter;
use App\Api\V1\LicenseePresenter;
use App\Api\V1\MemberContext;
use App\Api\V1\PrivateJson;
use App\Entity\Club;
use App\Entity\Group;
use App\Entity\Licensee;
use App\Repository\GroupRepository;
use App\Repository\LicenseeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\UnicodeString;

/**
 * The club of the selected licensee for the selected season, and its member directory (the "trombinoscope").
 */
final readonly class ClubController
{
    private const int DEFAULT_PAGE_SIZE = 30;

    private const int MAX_PAGE_SIZE = 100;

    private const string NO_GROUP = 'none';

    public function __construct(
        private MemberContext $context,
        private GroupRepository $groups,
        private LicenseeRepository $licensees,
        private ClubPresenter $clubPresenter,
        private LicenseePresenter $licenseePresenter,
    ) {
    }

    #[Route('/api/v1/club', name: 'api_v1_club', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $club = $this->context->requireClub();
        $season = $this->context->season();
        $members = $this->licensees->findByLicenseYear($club, $season);

        return PrivateJson::response([
            'club' => $this->clubPresenter->summary($club),
            'season' => $season,
            'member_count' => \count($members),
            'groups' => $this->groupsWithCounts($club, $members),
            'without_group_count' => $this->withoutGroupCount($members),
        ]);
    }

    /**
     * Query parameters: `group` (a group id of the club, or "none"), `q` (search in the displayed name),
     * `page` and `per_page`.
     */
    #[Route('/api/v1/club/members', name: 'api_v1_club_members', methods: ['GET'])]
    public function members(Request $request): JsonResponse
    {
        $club = $this->context->requireClub();
        $season = $this->context->season();
        $all = $this->licensees->findByLicenseYear($club, $season);

        $filtered = $this->filterByGroup($all, $this->groupFilter($request, $club));
        $summaries = array_map(fn (Licensee $licensee): array => $this->licenseePresenter->summary($licensee, $season), $filtered);
        $summaries = $this->search($summaries, trim((string) $request->query->get('q', '')));
        usort($summaries, static fn (array $a, array $b): int => self::fold($a['display_name']) <=> self::fold($b['display_name']));

        $page = $this->positiveInt($request, 'page', 1);
        $perPage = min($this->positiveInt($request, 'per_page', self::DEFAULT_PAGE_SIZE), self::MAX_PAGE_SIZE);

        return PrivateJson::response([
            'data' => \array_slice($summaries, ($page - 1) * $perPage, $perPage),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => \count($summaries),
                'total_in_club' => \count($all),
            ],
            'filters' => [
                'groups' => $this->groupsWithCounts($club, $all),
                'without_group_count' => $this->withoutGroupCount($all),
            ],
        ]);
    }

    /**
     * @param list<Licensee> $members
     *
     * @return list<array{id: int|null, name: string|null, description: string|null, member_count: int}>
     */
    private function groupsWithCounts(Club $club, array $members): array
    {
        $groups = [];
        foreach ($this->groups->findBy(['club' => $club], ['name' => 'ASC']) as $group) {
            $groups[] = [
                ...$this->clubPresenter->groupReference($group),
                'description' => $group->getDescription(),
                'member_count' => \count(array_filter($members, static fn (Licensee $m): bool => $m->getGroups()->contains($group))),
            ];
        }

        return $groups;
    }

    /**
     * @param list<Licensee> $members
     */
    private function withoutGroupCount(array $members): int
    {
        return \count(array_filter($members, static fn (Licensee $m): bool => $m->getGroups()->isEmpty()));
    }

    /**
     * @return Group|self::NO_GROUP|null
     */
    private function groupFilter(Request $request, Club $club): Group|string|null
    {
        $group = $request->query->get('group');
        if (null === $group || '' === $group) {
            return null;
        }

        if (self::NO_GROUP === $group) {
            return self::NO_GROUP;
        }

        foreach ($this->groups->findBy(['club' => $club]) as $candidate) {
            if (ctype_digit((string) $group) && $candidate->getId() === (int) $group) {
                return $candidate;
            }
        }

        throw new BadRequestHttpException('Unknown group.');
    }

    /**
     * @param list<Licensee> $members
     *
     * @return list<Licensee>
     */
    private function filterByGroup(array $members, Group|string|null $filter): array
    {
        return match (true) {
            $filter instanceof Group => array_values(array_filter($members, static fn (Licensee $m): bool => $m->getGroups()->contains($filter))),
            self::NO_GROUP === $filter => array_values(array_filter($members, static fn (Licensee $m): bool => $m->getGroups()->isEmpty())),
            default => $members,
        };
    }

    /**
     * Searches what the viewer can see (the displayed name): matching on a last name that is
     * shown as an initial would let a member find out other members' last names.
     *
     * @param list<array<string, mixed>> $summaries
     *
     * @return list<array<string, mixed>>
     */
    private function search(array $summaries, string $query): array
    {
        if ('' === $query) {
            return $summaries;
        }

        $needle = self::fold($query);

        return array_values(array_filter($summaries, static fn (array $s): bool => str_contains(self::fold($s['display_name']), $needle)));
    }

    private function positiveInt(Request $request, string $name, int $default): int
    {
        $value = $request->query->get($name);
        if (null === $value || '' === $value) {
            return $default;
        }

        $int = filter_var($value, \FILTER_VALIDATE_INT);
        if (false === $int || $int < 1) {
            throw new BadRequestHttpException(\sprintf('Invalid "%s" parameter.', $name));
        }

        return $int;
    }

    /**
     * Case- and accent-insensitive form used to sort and search names.
     */
    private static function fold(string $value): string
    {
        return new UnicodeString($value)->ascii()->lower()->toString();
    }
}
