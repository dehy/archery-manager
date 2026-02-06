<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Group;
use App\Repository\GroupRepository;
use App\Tests\application\LoggedInTestCase;

final class GroupManagementControllerTest extends LoggedInTestCase
{
    private const string URL_CREATE = '/admin/groups/create';

    // ── Manage Group ───────────────────────────────────────────────────

    public function testManageRequiresAdmin(): void
    {
        $client = self::createLoggedInAsUserClient();
        $groupId = $this->getFirstGroupId();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, \sprintf('/admin/groups/%d/manage', $groupId));
        $this->assertResponseStatusCodeSame(403);
    }

    public function testManageRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, \sprintf('/admin/groups/%d/manage', $groupId));
        $this->assertResponseIsSuccessful();
    }

    public function testManageDisplaysGroupMembers(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, \sprintf('/admin/groups/%d/manage', $groupId));
        $this->assertResponseIsSuccessful();
        // Should display some content
        $this->assertGreaterThan(0, $crawler->filter('body')->count());
    }

    // ── Add Member ─────────────────────────────────────────────────────

    public function testAddMemberRequiresAdmin(): void
    {
        $client = self::createLoggedInAsUserClient();
        $groupId = $this->getFirstGroupId();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, \sprintf('/admin/groups/%d/add-member', $groupId), [
            'licenseeId' => 1,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAddMemberRequiresPostMethod(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, \sprintf('/admin/groups/%d/add-member', $groupId));
        $this->assertResponseStatusCodeSame(405);
    }

    public function testAddMemberReturnsJsonError(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, \sprintf('/admin/groups/%d/add-member', $groupId), [
            'licenseeId' => 99999, // Non-existent
        ]);

        $this->assertResponseStatusCodeSame(404);
        $response = $client->getResponse();
        $this->assertJson($response->getContent());
    }

    // ── Remove Member ──────────────────────────────────────────────────

    public function testRemoveMemberRequiresAdmin(): void
    {
        $client = self::createLoggedInAsUserClient();
        $groupId = $this->getFirstGroupId();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, \sprintf('/admin/groups/%d/remove-member', $groupId), [
            'licenseeId' => 1,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRemoveMemberRequiresPostMethod(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, \sprintf('/admin/groups/%d/remove-member', $groupId));
        $this->assertResponseStatusCodeSame(405);
    }

    public function testRemoveMemberReturnsJsonError(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $groupId = $this->getFirstGroupId();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, \sprintf('/admin/groups/%d/remove-member', $groupId), [
            'licenseeId' => 99999,
        ]);

        $this->assertResponseStatusCodeSame(404);
        $response = $client->getResponse();
        $this->assertJson($response->getContent());
    }

    // ── Create Group ───────────────────────────────────────────────────

    public function testCreateRequiresAdmin(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_CREATE);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateRendersFormForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_CREATE);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testCreateSubmitCreatesGroup(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_CREATE);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer le groupe')->form();
        $form['group[name]'] = 'Test Group '.uniqid();
        $client->submit($form);

        // Should redirect to manage page
        $this->assertResponseRedirects();
        $this->assertStringContainsString('/admin/groups/', (string) $client->getResponse()->headers->get('Location'));
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
}
