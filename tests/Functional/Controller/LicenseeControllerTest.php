<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Licensee;
use App\Entity\User;
use App\Repository\LicenseeRepository;
use App\Tests\application\LoggedInTestCase;

class LicenseeControllerTest extends LoggedInTestCase
{
    private const string URL_INDEX = '/licensees';
    private const string URL_MY_PROFILE = '/my-profile';
    private const string URL_LICENSEE = '/licensee/';

    // ── Index ──────────────────────────────────────────────────────────

    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', self::URL_INDEX);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    public function testIndexRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request('GET', self::URL_INDEX);

        $this->assertResponseIsSuccessful();
    }

    public function testIndexRendersForUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request('GET', self::URL_INDEX);

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithGroupFilter(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // First load the index to see available groups
        $client->request('GET', self::URL_INDEX);
        $this->assertResponseIsSuccessful();

        // Try filter with a non-existent group (should still render fine)
        $client->request('GET', self::URL_INDEX.'?group=999');
        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithNoGroupFilter(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request('GET', self::URL_INDEX.'?group=no-group');
        $this->assertResponseIsSuccessful();
    }

    // ── My Profile ─────────────────────────────────────────────────────

    public function testMyProfileRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', self::URL_MY_PROFILE);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    public function testMyProfileRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request('GET', self::URL_MY_PROFILE);

        $this->assertResponseIsSuccessful();
    }

    public function testMyProfileRendersForUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request('GET', self::URL_MY_PROFILE);

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

        $client->request('GET', self::URL_LICENSEE.$licensee->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testShowNonExistentLicensee(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request('GET', self::URL_LICENSEE.'99999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowOtherLicenseeAsAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // Get the user's licensee to show (admin can see any licensee)
        $allLicensees = static::getContainer()->get(LicenseeRepository::class)->findAll();
        $this->assertNotEmpty($allLicensees);

        // Pick the last licensee (likely different from admin's)
        $licensee = end($allLicensees);
        $client->request('GET', self::URL_LICENSEE.$licensee->getId());

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

        $client->request('GET', self::URL_LICENSEE.$licensee->getId().'/picture');

        // Should return either the real image or the SVG placeholder
        $this->assertResponseIsSuccessful();
        $contentType = $client->getResponse()->headers->get('Content-Type');
        $this->assertTrue(
            str_contains($contentType, 'image/') || str_contains($contentType, 'svg'),
            'Expected image content type, got: '.$contentType
        );
    }

    public function testProfilePictureNonExistentLicensee(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request('GET', self::URL_LICENSEE.'99999/picture');

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

        $client->request('GET', self::URL_LICENSEE.$licensee->getId().'/edit');
        $this->assertResponseIsSuccessful();
    }

    public function testEditSubmitUpdatesLicensee(): void
    {
        $client = self::createLoggedInAsAdminClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $licensee = $user->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        $crawler = $client->request('GET', self::URL_LICENSEE.$licensee->getId().'/edit');
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
        $client->request('GET', self::URL_LICENSEE.$licensee->getId().'/sync');
        $this->assertResponseStatusCodeSame(405);
    }

    public function testSyncNonExistentLicensee(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // Post to non-existent licensee
        $client->request('POST', self::URL_LICENSEE.'99999/sync');
        $this->assertResponseStatusCodeSame(404);
    }

    // ── Access Control ─────────────────────────────────────────────────

    public function testUserCannotAccessOtherLicenseeProfile(): void
    {
        $client = self::createLoggedInAsUserClient();

        // Get the admin's licensee to attempt access
        $allLicensees = static::getContainer()->get(LicenseeRepository::class)->findAll();

        // Find a licensee not belonging to user1@ladg.com
        /** @var User $currentUser */
        $currentUser = $client->getContainer()->get('security.token_storage')->getToken()->getUser();
        $ownLicenseeIds = $currentUser->getLicensees()->map(fn (Licensee $l) => $l->getId())->toArray();

        $otherLicensee = null;
        foreach ($allLicensees as $l) {
            if (!\in_array($l->getId(), $ownLicenseeIds, true)) {
                $otherLicensee = $l;
                break;
            }
        }

        if ($otherLicensee instanceof Licensee) {
            $client->request('GET', self::URL_LICENSEE.$otherLicensee->getId());
            // Should be 403 since user is not admin, not coach, and not same club_admin
            $this->assertResponseStatusCodeSame(403);
        } else {
            $this->markTestSkipped('No other licensee available for access control test');
        }
    }
}
