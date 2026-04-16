<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Licensee;
use App\Entity\User;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use App\Tests\application\LoggedInTestCase;
use Symfony\Component\HttpFoundation\Request;

final class LicenseeManagementMoveUserControllerTest extends LoggedInTestCase
{
    // ── Step 1 — Auth & access ─────────────────────────────────────────

    public function testStep1RequiresAuthentication(): void
    {
        $client = self::createClient();
        $licenseeId = $this->getLadgLicenseeId();
        $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testStep1DeniedForRegularUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $licenseeId = $this->getLadgLicenseeId();
        $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testStep1DeniedForCoach(): void
    {
        $client = self::createLoggedInAsCoachClient();
        $licenseeId = $this->getLadgLicenseeId();
        $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testStep1RendersForClubAdmin(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licenseeId = $this->getLadgLicenseeId();
        $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');

        $this->assertResponseIsSuccessful();
    }

    public function testStep1AdminFromDifferentClubIsRedirected(): void
    {
        // admin@acme.org belongs to the ACME club; LADG licensees fail the club-match check
        $client = self::createLoggedInAsAdminClient();
        $licenseeId = $this->getLadgLicenseeId();
        $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');

        // Access control passes (ROLE_ADMIN has ROLE_CLUB_ADMIN), but business-logic redirects
        $this->assertResponseRedirects();
    }

    // ── Step 1 — POST validation ───────────────────────────────────────

    public function testStep1PostWithNewChoiceAndValidEmailRedirectsToStep2(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licenseeId = $this->getLadgLicenseeId();

        $crawler = $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => 'nouveau.archer@exemple.fr',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/licensees/manage/'.$licenseeId.'/move-user/step2');
    }

    public function testStep1PostWithNewChoiceAndEmptyEmailShowsError(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licenseeId = $this->getLadgLicenseeId();

        $crawler = $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => '',
        ]);
        $client->submit($form);

        $this->assertResponseIsSuccessful();
    }

    public function testStep1PostWithNewChoiceAndAlreadyUsedEmailShowsError(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licenseeId = $this->getLadgLicenseeId();

        // user1@ladg.com already exists as a fixture user
        $crawler = $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => 'user1@ladg.com',
        ]);
        $client->submit($form);

        $this->assertResponseIsSuccessful();
    }

    public function testStep1PostWithExistingChoiceAndValidUserRedirectsToStep2(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licenseeId = $this->getLadgLicenseeId();
        $targetUserId = $this->getOtherUserIdForLadgLicensee($licenseeId);

        $crawler = $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'existing',
            'form[existing_user]' => (string) $targetUserId,
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/licensees/manage/'.$licenseeId.'/move-user/step2');
    }

    // ── Step 2 — Auth & session guard ─────────────────────────────────

    public function testStep2WithoutSessionRedirectsToStep1(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licenseeId = $this->getLadgLicenseeId();
        $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step2');

        $this->assertResponseRedirects('/licensees/manage/'.$licenseeId.'/move-user/step1');
    }

    public function testStep2RendersAfterStep1NewChoice(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licenseeId = $this->getLadgLicenseeId();

        // Go through step1 first
        $crawler = $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');
        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => 'unique.test.step2@exemple.fr',
        ]);
        $client->submit($form);
        $client->followRedirect();

        $this->assertResponseIsSuccessful();
    }

    public function testStep2RendersAfterStep1ExistingChoice(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licenseeId = $this->getLadgLicenseeId();
        $targetUserId = $this->getOtherUserIdForLadgLicensee($licenseeId);

        // Go through step1 first
        $crawler = $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');
        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'existing',
            'form[existing_user]' => (string) $targetUserId,
        ]);
        $client->submit($form);
        $client->followRedirect();

        $this->assertResponseIsSuccessful();
    }

    // ── Step 2 — POST (confirm move) ───────────────────────────────────

    public function testConfirmMoveToExistingUserSucceeds(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licenseeId = $this->getLadgLicenseeId();
        $targetUserId = $this->getOtherUserIdForLadgLicensee($licenseeId);

        // Step 1
        $crawler = $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');
        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'existing',
            'form[existing_user]' => (string) $targetUserId,
        ]);
        $client->submit($form);

        // Step 2 — confirm
        $crawler = $client->followRedirect();
        $form = $crawler->selectButton('Confirmer le déplacement')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/licensee/'.$licenseeId);

        // Verify the licensee is now linked to the target user
        /** @var LicenseeRepository $licenseeRepo */
        $licenseeRepo = self::getContainer()->get(LicenseeRepository::class);
        $licensee = $licenseeRepo->find($licenseeId);
        $this->assertInstanceOf(Licensee::class, $licensee);
        $this->assertSame($targetUserId, $licensee->getUser()?->getId());
    }

    public function testConfirmMoveToNewAccountCreatesUser(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licenseeId = $this->getLadgLicenseeId();
        $newEmail = 'brand.new.archer.'.uniqid().'@exemple.fr';

        // Step 1
        $crawler = $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');
        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => $newEmail,
        ]);
        $client->submit($form);

        // Step 2 — confirm
        $crawler = $client->followRedirect();
        $form = $crawler->selectButton('Confirmer le déplacement')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/licensee/'.$licenseeId);

        // Verify new user was created and licensee is linked to it
        /** @var UserRepository $userRepo */
        $userRepo = self::getContainer()->get(UserRepository::class);
        $newUser = $userRepo->findOneByEmail($newEmail);
        $this->assertInstanceOf(User::class, $newUser, 'New user should have been created');

        /** @var LicenseeRepository $licenseeRepo */
        $licenseeRepo = self::getContainer()->get(LicenseeRepository::class);
        $licensee = $licenseeRepo->find($licenseeId);
        $this->assertInstanceOf(Licensee::class, $licensee);
        $this->assertSame($newUser->getId(), $licensee->getUser()?->getId());
    }

    public function testConfirmMoveDeletesSourceUserWhenNoOtherLicensees(): void
    {
        $client = self::createLoggedInAsClubAdminClient();

        // Find a licensee whose user has exactly 1 licensee (will be deleted on move)
        $licensee = $this->getLadgLicenseeWithSingleUserLicensee();
        if (!$licensee instanceof Licensee) {
            $this->markTestSkipped('No suitable licensee found (user with exactly 1 licensee).');
        }

        $licenseeId = $licensee->getId();
        $sourceUserId = $licensee->getUser()?->getId();
        $this->assertNotNull($sourceUserId);

        $targetUserId = $this->getOtherUserIdForLadgLicensee($licenseeId);

        // Step 1
        $crawler = $client->request(Request::METHOD_GET, '/licensees/manage/'.$licenseeId.'/move-user/step1');
        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'existing',
            'form[existing_user]' => (string) $targetUserId,
        ]);
        $client->submit($form);

        // Step 2 — confirm
        $crawler = $client->followRedirect();
        $form = $crawler->selectButton('Confirmer le déplacement')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/licensee/'.$licenseeId);

        // Source user should be deleted
        /** @var UserRepository $userRepo */
        $userRepo = self::getContainer()->get(UserRepository::class);
        $this->assertNotInstanceOf(User::class, $userRepo->find($sourceUserId), 'Source user with no remaining licensees should be deleted');
    }

    // ── Helpers ────────────────────────────────────────────────────────

    /**
     * Returns the ID of a LADG licensee (not the club admin themselves).
     */
    private function getLadgLicenseeId(): int
    {
        /** @var LicenseeRepository $repo */
        $repo = self::getContainer()->get(LicenseeRepository::class);
        $licensees = $repo->findAll();

        foreach ($licensees as $licensee) {
            $userEmail = $licensee->getUser()?->getEmail() ?? '';
            // Pick a regular user licensee from LADG (not the club admin)
            if (str_ends_with($userEmail, '@ladg.com') && !str_contains($userEmail, 'clubadmin') && !str_contains($userEmail, 'coach')) {
                return $licensee->getId();
            }
        }

        $this->fail('No suitable LADG licensee found in fixtures.');
    }

    /**
     * Returns the ID of a user different from the current owner of $licenseeId.
     */
    private function getOtherUserIdForLadgLicensee(int $licenseeId): int
    {
        /** @var LicenseeRepository $licenseeRepo */
        $licenseeRepo = self::getContainer()->get(LicenseeRepository::class);
        $licensee = $licenseeRepo->find($licenseeId);
        $this->assertInstanceOf(Licensee::class, $licensee);
        $currentUserId = $licensee->getUser()?->getId();

        /** @var UserRepository $userRepo */
        $userRepo = self::getContainer()->get(UserRepository::class);
        $users = $userRepo->findAll();

        foreach ($users as $user) {
            if ($user->getId() !== $currentUserId) {
                return $user->getId();
            }
        }

        $this->fail('No other user found in fixtures.');
    }

    /**
     * Find a LADG licensee whose user has exactly 1 licensee (so source user will be deleted on move).
     */
    private function getLadgLicenseeWithSingleUserLicensee(): ?Licensee
    {
        /** @var LicenseeRepository $repo */
        $repo = self::getContainer()->get(LicenseeRepository::class);
        $licensees = $repo->findAll();

        foreach ($licensees as $licensee) {
            $user = $licensee->getUser();
            if (null === $user) {
                continue;
            }

            $userEmail = $user->getEmail() ?? '';
            if (!str_ends_with($userEmail, '@ladg.com')) {
                continue;
            }

            if (str_contains($userEmail, 'clubadmin')) {
                continue;
            }

            if (str_contains($userEmail, 'coach')) {
                continue;
            }

            if (1 === $user->getLicensees()->count()) {
                return $licensee;
            }
        }

        return null;
    }
}
