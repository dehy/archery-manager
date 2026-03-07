<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Group;
use App\Repository\GroupRepository;
use App\Repository\LicenseeRepository;
use App\Tests\application\LoggedInTestCase;
use Symfony\Component\HttpFoundation\Request;

final class GroupManagementControllerTest extends LoggedInTestCase
{
    private const string URL_CREATE = '/groups/create';

    private const string URL_MANAGE = '/groups/%d/manage';

    private const string URL_ADD_MEMBER = '/groups/%d/add-member';

    private const string URL_REMOVE_MEMBER = '/groups/%d/remove-member';

    // ── Manage Group ───────────────────────────────────────────────────

    public function testManageRequiresAdmin(): void
    {
        $client = self::createLoggedInAsUserClient();
        $groupId = $this->getFirstGroupId();

        $client->request(Request::METHOD_GET, \sprintf(self::URL_MANAGE, $groupId));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testManageRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $client->request(Request::METHOD_GET, \sprintf(self::URL_MANAGE, $groupId));
        $this->assertResponseIsSuccessful();
    }

    public function testManageDisplaysGroupMembers(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $crawler = $client->request(Request::METHOD_GET, \sprintf(self::URL_MANAGE, $groupId));
        $this->assertResponseIsSuccessful();
        // Should display some content
        $this->assertGreaterThan(0, $crawler->filter('body')->count());
    }

    // ── Add Member ─────────────────────────────────────────────────────

    public function testAddMemberRequiresAdmin(): void
    {
        $client = self::createLoggedInAsUserClient();
        $groupId = $this->getFirstGroupId();

        $client->request(Request::METHOD_POST, \sprintf(self::URL_ADD_MEMBER, $groupId), [
            'licenseeId' => 1,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAddMemberRequiresPostMethod(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $client->request(Request::METHOD_GET, \sprintf(self::URL_ADD_MEMBER, $groupId));
        $this->assertResponseStatusCodeSame(405);
    }

    public function testAddMemberReturnsJsonError(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $crawler = $client->request(Request::METHOD_GET, \sprintf(self::URL_MANAGE, $groupId));
        $this->assertResponseIsSuccessful();

        // BrowserKit automatically includes the hidden _token field from the rendered form
        $form = $crawler->filter('#add-member-form')->form([
            'group_member_action[licenseeId]' => '99999',
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(404);
        $this->assertJson($client->getResponse()->getContent());
    }

    // ── Remove Member ──────────────────────────────────────────────────

    public function testRemoveMemberRequiresAdmin(): void
    {
        $client = self::createLoggedInAsUserClient();
        $groupId = $this->getFirstGroupId();

        $client->request(Request::METHOD_POST, \sprintf(self::URL_REMOVE_MEMBER, $groupId), [
            'licenseeId' => 1,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRemoveMemberRequiresPostMethod(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $client->request(Request::METHOD_GET, \sprintf(self::URL_REMOVE_MEMBER, $groupId));
        $this->assertResponseStatusCodeSame(405);
    }

    public function testRemoveMemberReturnsJsonError(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $crawler = $client->request(Request::METHOD_GET, \sprintf(self::URL_MANAGE, $groupId));
        $this->assertResponseIsSuccessful();

        // BrowserKit automatically includes the hidden _token field from the rendered form
        $form = $crawler->filter('#remove-member-form')->form([
            'group_member_action[licenseeId]' => '99999',
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(404);
        $this->assertJson($client->getResponse()->getContent());
    }

    // ── Create Group ───────────────────────────────────────────────────

    public function testCreateRequiresAdmin(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(Request::METHOD_GET, self::URL_CREATE);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateRendersFormForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(Request::METHOD_GET, self::URL_CREATE);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCreateSubmitCreatesGroup(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $crawler = $client->request(Request::METHOD_GET, self::URL_CREATE);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer le groupe')->form();
        $form['group[name]'] = 'Test Group '.uniqid();
        $client->submit($form);

        // Should redirect to manage page
        $this->assertResponseRedirects();
        $this->assertStringContainsString('/groups/', (string) $client->getResponse()->headers->get('Location'));
    }

    // ── Manage (access denied for other club's group) ─────────────────

    public function testManageDeniedForOtherClubGroup(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getLadgGroupId();

        $client->request(Request::METHOD_GET, \sprintf(self::URL_MANAGE, $groupId));

        $this->assertResponseStatusCodeSame(403);
    }

    // ── resolveMemberAction: wrong club ────────────────────────────────

    public function testAddMemberDeniedForOtherClubGroup(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getLadgGroupId();

        // POST directly (no CSRF needed – club check happens first)
        $client->request(Request::METHOD_POST, \sprintf(self::URL_ADD_MEMBER, $groupId));

        $this->assertResponseStatusCodeSame(403);
        $this->assertJson($client->getResponse()->getContent());
    }

    // ── resolveMemberAction: invalid / missing form ────────────────────

    public function testAddMemberInvalidForm(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        // POST with no form data → form is never submitted → 400
        $client->request(Request::METHOD_POST, \sprintf(self::URL_ADD_MEMBER, $groupId));

        $this->assertResponseStatusCodeSame(400);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testRemoveMemberInvalidForm(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        // POST with no form data → form is never submitted → 400
        $client->request(Request::METHOD_POST, \sprintf(self::URL_REMOVE_MEMBER, $groupId));

        $this->assertResponseStatusCodeSame(400);
        $this->assertJson($client->getResponse()->getContent());
    }

    // ── removeMember: licensee not in group ────────────────────────────

    public function testRemoveMemberNotInGroup(): void
    {
        $client = self::createLoggedInAsAdminClient();
        // Use a group that has no members in fixtures so any licensed ladb
        // member is guaranteed to NOT be in it
        $emptyGroupId = $this->getEmptyLabdGroupId();

        $crawler = $client->request(Request::METHOD_GET, \sprintf(self::URL_MANAGE, $emptyGroupId));
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#remove-member-form')->form([
            'group_member_action[licenseeId]' => (string) $this->getLicenseeNotInGroupId($emptyGroupId),
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJson($client->getResponse()->getContent());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    // ── removeMember: success ──────────────────────────────────────────

    public function testRemoveMemberSuccess(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $crawler = $client->request(Request::METHOD_GET, \sprintf(self::URL_MANAGE, $groupId));
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#remove-member-form')->form([
            'group_member_action[licenseeId]' => (string) $this->getLicenseeInGroupId($groupId),
        ]);
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
    }

    // ── Helper ─────────────────────────────────────────────────────────

    private function getFirstGroupId(): int
    {
        $groupRepo = self::getContainer()->get(GroupRepository::class);

        // Get the group named 'group_ladb_competiteurs_adultes' which the admin belongs to
        $group = $groupRepo->findOneBy(['name' => 'Compétiteurs Adultes']);

        if (!$group) {
            // Fallback: get any group from club_ladb
            $allGroups = $groupRepo->findAll();
            foreach ($allGroups as $g) {
                if ('Les Archers du Bosquet' === $g->getClub()->getName()) {
                    $group = $g;
                    break;
                }
            }
        }

        $this->assertInstanceOf(Group::class, $group, "Could not find admin's group");

        return $group->getId();
    }

    private function getLadgGroupId(): int
    {
        $groupRepo = self::getContainer()->get(GroupRepository::class);
        $group = $groupRepo->findOneBy(['name' => 'Groupe Compétiteurs']);

        $this->assertInstanceOf(Group::class, $group, 'LaDG group not found in fixtures');

        return $group->getId();
    }

    private function getEmptyLabdGroupId(): int
    {
        // 'Débutants Adultes' for ladb has no licensees in fixtures
        $groupRepo = self::getContainer()->get(GroupRepository::class);
        $group = $groupRepo->findOneBy(['name' => 'Débutants Adultes']);
        $this->assertInstanceOf(Group::class, $group, 'LADB Débutants Adultes group not found');

        return $group->getId();
    }

    private function getLicenseeNotInGroupId(int $groupId): int
    {
        $groupRepo = self::getContainer()->get(GroupRepository::class);
        /** @var Group $group */
        $group = $groupRepo->find($groupId);
        $this->assertInstanceOf(Group::class, $group);

        /** @var LicenseeRepository $licenseeRepo */
        $licenseeRepo = self::getContainer()->get(LicenseeRepository::class);
        $allLicensees = $licenseeRepo->findByLicenseYear($group->getClub(), 2026);

        foreach ($allLicensees as $licensee) {
            if (!$group->getLicensees()->contains($licensee)) {
                return $licensee->getId();
            }
        }

        $this->fail('No licensee outside the group found for club ' . $group->getClub()->getName());
    }

    private function getLicenseeInGroupId(int $groupId): int
    {
        $groupRepo = self::getContainer()->get(GroupRepository::class);
        /** @var Group $group */
        $group = $groupRepo->find($groupId);
        $this->assertInstanceOf(Group::class, $group);

        $members = $group->getLicensees();
        $this->assertGreaterThan(0, $members->count(), 'Group has no members; cannot test remove');

        return $members->first()->getId();
    }
}
