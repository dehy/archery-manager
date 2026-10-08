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
use App\Repository\LicenseeAttachmentRepository;
use App\Repository\LicenseeRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\UnicodeString;

/**
 * The club of the selected licensee for the selected season, and its member directory (the "trombinoscope").
 *
 * The directory is built from one query for the whole club: filtering, searching and sorting happen in
 * PHP on the displayed names (the viewer must not be able to search or order on what they cannot see).
 * That is fine for a club, but it is the place to push into SQL if clubs ever get large.
 */
final readonly class ClubController
{
    private const int DEFAULT_PAGE_SIZE = 30;

    private const int MAX_PAGE_SIZE = 100;

    /** Beyond this the offset arithmetic could overflow; nobody pages through 3 million members. */
    private const int MAX_PAGE = 100000;

    private const string NO_GROUP = 'none';

    public function __construct(
        private MemberContext $context,
        private GroupRepository $groups,
        private LicenseeRepository $licensees,
        private LicenseeAttachmentRepository $attachments,
        private ClubPresenter $clubPresenter,
        private LicenseePresenter $licenseePresenter,
    ) {
    }

    #[Route('/api/v1/club', name: 'api_v1_club', methods: ['GET'])]
    public function show(): JsonResponse
    {
        $club = $this->context->requireClub();
        $season = $this->context->season();
        $members = $this->directory($club, $season);

        return PrivateJson::response([
            'club' => $this->clubPresenter->summary($club),
            'season' => $season,
            'member_count' => \count($members),
            'groups' => $this->groupsWithCounts($this->groups->findBy(['club' => $club], ['name' => 'ASC']), $members),
            'without_group_count' => $this->withoutGroupCount($members),
        ]);
    }

    /**
     * Query parameters: `group` (a group id of the club, or "none"), `q` (search in the displayed name),
     * `page` and `per_page` (larger values are capped).
     */
    #[Route('/api/v1/club/members', name: 'api_v1_club_members', methods: ['GET'])]
    public function members(Request $request): JsonResponse
    {
        $club = $this->context->requireClub();
        $season = $this->context->season();
        $clubGroups = $this->groups->findBy(['club' => $club], ['name' => 'ASC']);
        $all = $this->directory($club, $season);

        $matching = $this->search($this->filterByGroup($all, $this->groupFilter($request, $clubGroups)), trim((string) $request->query->get('q', '')));
        usort($matching, static fn (array $a, array $b): int => [$a['key'], $a['licensee']->getId()] <=> [$b['key'], $b['licensee']->getId()]);

        $page = $this->positiveInt($request, 'page', 1, self::MAX_PAGE);
        $perPage = min($this->positiveInt($request, 'per_page', self::DEFAULT_PAGE_SIZE), self::MAX_PAGE_SIZE);
        $pageLicensees = array_map(static fn (array $entry): Licensee => $entry['licensee'], \array_slice($matching, ($page - 1) * $perPage, $perPage));
        $withPicture = $this->attachments->profilePictureOwners($pageLicensees);

        return PrivateJson::response([
            'data' => array_map(
                fn (Licensee $licensee): array => $this->licenseePresenter->summary($licensee, $season, $club, isset($withPicture[$licensee->getId()])),
                $pageLicensees,
            ),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => \count($matching),
                'total_in_club' => \count($all),
            ],
            'filters' => [
                'groups' => $this->groupsWithCounts($clubGroups, $all),
                'without_group_count' => $this->withoutGroupCount($all),
            ],
        ]);
    }

    /**
     * Every member of the club for the season, with the groups of the club they are in and the
     * name the viewer may see (and its folded form, used to sort and search).
     *
     * @return list<array{licensee: Licensee, groups: list<Group>, name: string, key: string}>
     */
    private function directory(Club $club, int $season): array
    {
        $entries = [];
        foreach ($this->licensees->findForDirectory($club, $season) as $licensee) {
            $name = $this->licenseePresenter->displayName($licensee);
            $entries[] = [
                'licensee' => $licensee,
                'groups' => $this->licenseePresenter->groupsOf($licensee, $club),
                'name' => $name,
                'key' => $this->fold($name),
            ];
        }

        return $entries;
    }

    /**
     * @param list<Group>                                                                      $groups
     * @param list<array{licensee: Licensee, groups: list<Group>, name: string, key: string}> $members
     *
     * @return list<array{id: int|null, name: string|null, description: string|null, member_count: int}>
     */
    private function groupsWithCounts(array $groups, array $members): array
    {
        $result = [];
        foreach ($groups as $group) {
            $result[] = [
                ...$this->clubPresenter->groupReference($group),
                'description' => $group->getDescription(),
                'member_count' => \count(array_filter($members, static fn (array $m): bool => \in_array($group, $m['groups'], true))),
            ];
        }

        return $result;
    }

    /**
     * @param list<array{licensee: Licensee, groups: list<Group>, name: string, key: string}> $members
     */
    private function withoutGroupCount(array $members): int
    {
        return \count(array_filter($members, static fn (array $m): bool => [] === $m['groups']));
    }

    /**
     * @param list<Group> $clubGroups
     *
     * @return Group|self::NO_GROUP|null
     */
    private function groupFilter(Request $request, array $clubGroups): Group|string|null
    {
        $requested = $request->query->get('group');
        if (null === $requested || '' === $requested) {
            return null;
        }

        if (self::NO_GROUP === $requested) {
            return self::NO_GROUP;
        }

        foreach ($clubGroups as $group) {
            if (ctype_digit((string) $requested) && $group->getId() === (int) $requested) {
                return $group;
            }
        }

        throw new BadRequestHttpException('Unknown group.');
    }

    /**
     * @param list<array{licensee: Licensee, groups: list<Group>, name: string, key: string}> $members
     *
     * @return list<array{licensee: Licensee, groups: list<Group>, name: string, key: string}>
     */
    private function filterByGroup(array $members, Group|string|null $filter): array
    {
        return match (true) {
            $filter instanceof Group => array_values(array_filter($members, static fn (array $m): bool => \in_array($filter, $m['groups'], true))),
            self::NO_GROUP === $filter => array_values(array_filter($members, static fn (array $m): bool => [] === $m['groups'])),
            default => $members,
        };
    }

    /**
     * Searches what the viewer can see (the displayed name): matching on a last name that is
     * shown as an initial would let a member find out other members' last names.
     *
     * @param list<array{licensee: Licensee, groups: list<Group>, name: string, key: string}> $members
     *
     * @return list<array{licensee: Licensee, groups: list<Group>, name: string, key: string}>
     */
    private function search(array $members, string $query): array
    {
        if ('' === $query) {
            return $members;
        }

        $needle = $this->fold($query);

        return array_values(array_filter($members, static fn (array $m): bool => str_contains((string) $m['key'], $needle)));
    }

    private function positiveInt(Request $request, string $name, int $default, ?int $max = null): int
    {
        $value = $request->query->get($name);
        if (null === $value || '' === $value) {
            return $default;
        }

        $int = filter_var($value, \FILTER_VALIDATE_INT);
        if (false === $int || $int < 1 || (null !== $max && $int > $max)) {
            throw new BadRequestHttpException(\sprintf('Invalid "%s" parameter.', $name));
        }

        return $int;
    }

    /**
     * Case- and accent-insensitive form used to sort and search names.
     */
    private function fold(string $value): string
    {
        return new UnicodeString($value)->ascii()->lower()->toString();
    }
}
