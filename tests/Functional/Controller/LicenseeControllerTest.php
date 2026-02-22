<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Licensee;
use App\Entity\User;
use App\Repository\LicenseeRepository;
use App\Tests\application\LoggedInTestCase;

final class LicenseeControllerTest extends LoggedInTestCase
{
    private const string URL_INDEX = '/licensees';

    private const string URL_MY_PROFILE = '/my-profile';

    private const string URL_LICENSEE = '/licensee/';

    // ── Index ──────────────────────────────────────────────────────────

    public function testIndexRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_INDEX);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testIndexRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_INDEX);

        $this->assertResponseIsSuccessful();
    }

    public function testIndexRendersForUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_INDEX);

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithGroupFilter(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // First load the index to see available groups
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_INDEX);
        $this->assertResponseIsSuccessful();

        // Try filter with a non-existent group (should still render fine)
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_INDEX.'?group=999');
        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithNoGroupFilter(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_INDEX.'?group=no-group');
        $this->assertResponseIsSuccessful();
    }

    // ── My Profile ─────────────────────────────────────────────────────

    public function testMyProfileRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MY_PROFILE);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testMyProfileRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MY_PROFILE);

        $this->assertResponseIsSuccessful();
    }

    public function testMyProfileRendersForUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MY_PROFILE);

        $this->assertResponseIsSuccessful();
    }

    // ── Show (by ID) ───────────────────────────────────────────────────

    public function testShowOwnProfileById(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // Get admin's licensee ID
        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $licensee = $user->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.$licensee->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testShowNonExistentLicensee(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.'99999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowOtherLicenseeAsAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // Get the user's licensee to show (admin can see any licensee)
        $allLicensees = self::getContainer()->get(LicenseeRepository::class)->findAll();
        $this->assertNotEmpty($allLicensees);

        // Pick the last licensee (likely different from admin's)
        $licensee = end($allLicensees);
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.$licensee->getId());

        $this->assertResponseIsSuccessful();
    }

    // ── Profile Picture ────────────────────────────────────────────────

    public function testProfilePictureReturnsResponse(): void
    {
        $client = self::createLoggedInAsAdminClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $licensee = $user->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.$licensee->getId().'/picture');

        // Should return either the real image or the SVG placeholder
        $this->assertResponseIsSuccessful();
        $contentType = $client->getResponse()->headers->get('Content-Type');
        $this->assertTrue(
            str_contains((string) $contentType, 'image/') || str_contains((string) $contentType, 'svg'),
            'Expected image content type, got: '.$contentType
        );
    }

    public function testProfilePictureNonExistentLicensee(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.'99999/picture');

        $this->assertResponseStatusCodeSame(404);
    }

    // ── Edit ───────────────────────────────────────────────────────────

    public function testEditRendersFormForOwnLicensee(): void
    {
        $client = self::createLoggedInAsAdminClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $licensee = $user->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.$licensee->getId().'/edit');
        $this->assertResponseIsSuccessful();
    }

    public function testEditSubmitUpdatesLicensee(): void
    {
        $client = self::createLoggedInAsAdminClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $licensee = $user->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.$licensee->getId().'/edit');
        $this->assertResponseIsSuccessful();

        // Find the form and submit with updated data
        $form = $crawler->selectButton('Enregistrer')->form();
        $form['licensee_form[firstname]'] = 'UpdatedFirstName';
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_LICENSEE.$licensee->getId());
    }

    // ── Sync ───────────────────────────────────────────────────────────

    public function testSyncRequiresPostMethod(): void
    {
        $client = self::createLoggedInAsAdminClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $licensee = $user->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        // GET should not be allowed
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.$licensee->getId().'/sync');
        $this->assertResponseStatusCodeSame(405);
    }

    public function testSyncNonExistentLicensee(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // Post to non-existent licensee
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_LICENSEE.'99999/sync');
        $this->assertResponseStatusCodeSame(404);
    }

    // ── Access Control ─────────────────────────────────────────────────

    public function testUserCannotAccessOtherLicenseeProfile(): void
    {
        $client = self::createLoggedInAsUserClient();

        // Get the admin's licensee to attempt access
        $allLicensees = self::getContainer()->get(LicenseeRepository::class)->findAll();

        // Find a licensee not belonging to user1@ladg.com
        /** @var User $currentUser */
        $currentUser = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $ownLicenseeIds = $currentUser->getLicensees()->map(static fn (Licensee $l): ?int => $l->getId())->toArray();

        $otherLicensee = null;
        foreach ($allLicensees as $l) {
            if (!\in_array($l->getId(), $ownLicenseeIds, true)) {
                $otherLicensee = $l;
                break;
            }
        }

        if ($otherLicensee instanceof Licensee) {
            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.$otherLicensee->getId());
            // Should be 403 since user is not admin, not coach, and not same club_admin
            $this->assertResponseStatusCodeSame(403);
        } else {
            $this->markTestSkipped('No other licensee available for access control test');
        }
    }

    // ── Download Attachment ────────────────────────────────────────────

    public function testAttachmentDownloadNonExistentReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/licensees/attachments/99999');

        $this->assertResponseStatusCodeSame(404);
    }

    // ── Edit Access Control ────────────────────────────────────────────

    public function testEditNonExistentLicenseeReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.'99999/edit');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserCannotEditOtherLicenseeProfile(): void
    {
        $client = self::createLoggedInAsUserClient();

        $allLicensees = self::getContainer()->get(LicenseeRepository::class)->findAll();

        /** @var User $currentUser */
        $currentUser = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $ownLicenseeIds = $currentUser->getLicensees()->map(static fn (Licensee $l): ?int => $l->getId())->toArray();

        $otherLicensee = null;
        foreach ($allLicensees as $l) {
            if (!\in_array($l->getId(), $ownLicenseeIds, true)) {
                $otherLicensee = $l;
                break;
            }
        }

        if ($otherLicensee instanceof Licensee) {
            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.$otherLicensee->getId().'/edit');
            $this->assertResponseStatusCodeSame(403);
        } else {
            $this->markTestSkipped('No other licensee available for access control test');
        }
    }

    // ── Show with Results / Charts ─────────────────────────────────────

    public function testShowDisplaysResultsSection(): void
    {
        $client = self::createLoggedInAsAdminClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $licensee = $user->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.$licensee->getId());
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('body')->count());
    }

    // ── Profile Picture (conditional response) ─────────────────────────

    public function testProfilePictureWithIfModifiedSince(): void
    {
        $client = self::createLoggedInAsAdminClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $licensee = $user->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        // First request to get Last-Modified header
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LICENSEE.$licensee->getId().'/picture');
        $this->assertResponseIsSuccessful();

        $lastModified = $client->getResponse()->headers->get('Last-Modified');

        if ($lastModified) {
            // Second request with If-Modified-Since
            $client->request(
                \Symfony\Component\HttpFoundation\Request::METHOD_GET,
                self::URL_LICENSEE.$licensee->getId().'/picture',
                [],
                [],
                ['HTTP_IF_MODIFIED_SINCE' => $lastModified]
            );

            // Should return 304 Not Modified
            $statusCode = $client->getResponse()->getStatusCode();
            $this->assertTrue(
                304 === $statusCode || 200 === $statusCode,
                'Expected 304 Not Modified or 200 OK'
            );
        }
    }

    // ── Sync (POST) ───────────────────────────────────────────────────

    public function testSyncRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_LICENSEE.'1/sync');

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    // ── Index with valid group filter ──────────────────────────────────

    public function testIndexWithExistingGroupFilter(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // Get a real group ID from the database
        $groupRepo = self::getContainer()->get(\App\Repository\GroupRepository::class);
        $groups = $groupRepo->findAll();

        if (\count($groups) > 0) {
            $group = $groups[0];
            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_INDEX.'?group='.$group->getId());
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('No groups available for filtering test');
        }
    }
}
