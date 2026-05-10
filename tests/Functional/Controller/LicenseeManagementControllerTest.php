<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class LicenseeManagementControllerTest extends LoggedInTestCase
{
    private const string URL_CHOICE = '/licensees/manage/new';

    private const string URL_MANUAL = '/licensees/manage/new/manual';

    private const string URL_STEP1 = '/licensees/manage/new/step1';

    private const string URL_STEP2 = '/licensees/manage/new/step2';

    private const string URL_STEP3 = '/licensees/manage/new/step3';

    private const string URL_STEP4 = '/licensees/manage/new/step4';

    private const string URL_CANCEL = '/licensees/manage/cancel';

    public function testNewChoicePageRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_CHOICE);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testNewChoicePageRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_CHOICE);

        $this->assertResponseIsSuccessful();
    }

    public function testNewChoicePostSyncRedirects(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_CHOICE, [
            'choice' => 'sync',
            'ffta_member_code' => '123456A',
        ]);

        $this->assertResponseRedirects('/licensees/manage/new/sync/123456A');
    }

    public function testNewChoicePostManualRedirects(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_CHOICE, [
            'choice' => 'manual',
        ]);

        $this->assertResponseRedirects(self::URL_MANUAL);
    }

    public function testNewManualInitializesSessionAndRedirects(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);

        $this->assertResponseRedirects(self::URL_STEP1);
    }

    public function testStep1RequiresSessionData(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_STEP1);

        $this->assertResponseRedirects(self::URL_CHOICE);
    }

    public function testStep1RendersFormAfterManualInit(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);
        $client->followRedirect();

        $this->assertResponseIsSuccessful();
    }

    public function testStep2RequiresStep1Data(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // Init session via manual, then access step2 without licensee data
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_STEP2);

        $this->assertResponseRedirects(self::URL_STEP1);
    }

    public function testStep3RequiresStep2Data(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);
        $client->followRedirect();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_STEP3);

        $this->assertResponseRedirects(self::URL_STEP2);
    }

    public function testStep4RequiresStep3Data(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);
        $client->followRedirect();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_STEP4);

        $this->assertResponseRedirects(self::URL_STEP3);
    }

    public function testCancelClearsSessionAndRedirects(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_CANCEL);

        $this->assertResponseRedirects('/licensees');
    }

    public function testStep2RendersFormWithValidSessionData(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);

        $crawler = $client->followRedirect();

        $form = $crawler->selectButton('Suivant')->form([
            'licensee_form[firstname]' => 'Jean',
            'licensee_form[lastname]' => 'Dupont',
            'licensee_form[gender]' => 'M',
            'licensee_form[birthdate]' => '1990-05-15',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects(self::URL_STEP2);

        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testStep3RendersFormAfterStep2(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // Step 1
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);

        $crawler = $client->followRedirect();

        $form = $crawler->selectButton('Suivant')->form([
            'licensee_form[firstname]' => 'Jean',
            'licensee_form[lastname]' => 'Dupont',
            'licensee_form[gender]' => 'M',
            'licensee_form[birthdate]' => '1990-05-15',
        ]);
        $client->submit($form);

        // Step 2
        $crawler = $client->followRedirect();
        $form = $crawler->selectButton('Suivant')->form([
            'license_form[type]' => 'A',
            'license_form[category]' => 'A',
            'license_form[ageCategory]' => 'S1',
        ]);
        // Tick the first available activity checkbox
        $activityCheckboxes = $crawler->filter('input[name="license_form[activities][]"]');
        if ($activityCheckboxes->count() > 0) {
            $form['license_form[activities]'] = [$activityCheckboxes->first()->attr('value')];
        }

        $client->submit($form);

        $this->assertResponseRedirects(self::URL_STEP3);

        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testNewFromFftaWithInvalidCodeRedirects(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, '/licensees/manage/new/sync/INVALID1');

        $this->assertResponseRedirects(self::URL_CHOICE);
    }

    public function testUserRoleCannotAccessManagement(): void
    {
        $client = self::createLoggedInAsUserClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_CHOICE);

        $this->assertResponseStatusCodeSame(403);
    }

    // ── ROLE_CLUB_ADMIN Access ────────────────────────────────────────

    public function testClubAdminCanAccessChoice(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_CHOICE);

        $this->assertResponseIsSuccessful();
    }

    public function testClubAdminChoicePostSyncRedirects(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_CHOICE, [
            'choice' => 'sync',
            'ffta_member_code' => '123456A',
        ]);

        $this->assertResponseRedirects('/licensees/manage/new/sync/123456A');
    }

    public function testClubAdminChoicePostManualRedirects(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_CHOICE, [
            'choice' => 'manual',
        ]);

        $this->assertResponseRedirects(self::URL_MANUAL);
    }

    public function testClubAdminCanStartManualWizard(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);

        $this->assertResponseRedirects(self::URL_STEP1);
    }

    public function testClubAdminCanCompleteStep1(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);

        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Suivant')->form([
            'licensee_form[firstname]' => 'Club',
            'licensee_form[lastname]' => 'Admin',
            'licensee_form[gender]' => 'M',
            'licensee_form[birthdate]' => '1990-01-01',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_STEP2);
    }

    public function testClubAdminCanAccessCancel(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_CANCEL);

        $this->assertResponseRedirects('/licensees');
    }

    // ── Full Wizard Flow ──────────────────────────────────────────────

    public function testFullWizardFlowWithNewUser(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // Step 0: Manual choice
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);
        $this->assertResponseRedirects(self::URL_STEP1);

        // Step 1: Fill licensee info
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Suivant')->form([
            'licensee_form[firstname]' => 'Pierre',
            'licensee_form[lastname]' => 'Martin',
            'licensee_form[gender]' => 'M',
            'licensee_form[birthdate]' => '1985-03-20',
        ]);
        $client->submit($form);
        $this->assertResponseRedirects(self::URL_STEP2);

        // Step 2: Fill license info
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Suivant')->form([
            'license_form[type]' => 'A',
            'license_form[category]' => 'A',
            'license_form[ageCategory]' => 'S1',
        ]);
        $activityCheckboxes = $crawler->filter('input[name="license_form[activities][]"]');
        if ($activityCheckboxes->count() > 0) {
            $form['license_form[activities]'] = [$activityCheckboxes->first()->attr('value')];
        }

        $client->submit($form);
        $this->assertResponseRedirects(self::URL_STEP3);

        // Step 3: Select groups (at least one required to proceed)
        $crawler = $client->followRedirect();
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Suivant')->form();
        $groupCheckboxes = $crawler->filter('input[name="licensee_group_selection[groups][]"]');
        if ($groupCheckboxes->count() > 0) {
            $form['licensee_group_selection[groups]'] = [$groupCheckboxes->first()->attr('value')];
        }

        $client->submit($form);
        $this->assertResponseRedirects(self::URL_STEP4);

        // Step 4: User link page renders
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testStep4SubmitWithNewUserCreatesLicensee(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $uniqueId = uniqid();

        // Walk through all steps
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);
        $crawler = $client->followRedirect();

        $form = $crawler->selectButton('Suivant')->form([
            'licensee_form[firstname]' => 'Test'.$uniqueId,
            'licensee_form[lastname]' => 'Wizard',
            'licensee_form[gender]' => 'F',
            'licensee_form[birthdate]' => '2000-06-15',
        ]);
        $client->submit($form);

        $crawler = $client->followRedirect();
        $form = $crawler->selectButton('Suivant')->form([
            'license_form[type]' => 'A',
            'license_form[category]' => 'A',
            'license_form[ageCategory]' => 'S1',
        ]);
        $activityCheckboxes = $crawler->filter('input[name="license_form[activities][]"]');
        if ($activityCheckboxes->count() > 0) {
            $form['license_form[activities]'] = [$activityCheckboxes->first()->attr('value')];
        }

        $client->submit($form);

        $crawler = $client->followRedirect();
        $form = $crawler->selectButton('Suivant')->form();
        $groupCheckboxes = $crawler->filter('input[name="licensee_group_selection[groups][]"]');
        if ($groupCheckboxes->count() > 0) {
            $form['licensee_group_selection[groups]'] = [$groupCheckboxes->first()->attr('value')];
        }

        $client->submit($form);

        $crawler = $client->followRedirect();

        // Step 4: Submit with new user
        $form = $crawler->selectButton('Créer le licencié')->form([
            'licensee_user_link[user_choice]' => 'new',
            'licensee_user_link[email]' => 'wizard-test-'.$uniqueId.'@example.com',
        ]);
        $client->submit($form);

        // Should redirect to the created licensee's profile
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isRedirection(),
            'Expected redirect after successful creation'
        );
    }

    // ── Access Control ────────────────────────────────────────────────

    public function testUserRoleCannotAccessStep1(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_STEP1);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserRoleCannotAccessStep2(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_STEP2);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserRoleCannotAccessStep3(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_STEP3);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserRoleCannotAccessStep4(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_STEP4);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserRoleCannotAccessCancel(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_CANCEL);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserRoleCannotAccessManual(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL);

        $this->assertResponseStatusCodeSame(403);
    }

    // ── Choice Form POST ──────────────────────────────────────────────

    public function testNewChoicePostWithMissingCodeRedirectsToManual(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_CHOICE, [
            'choice' => 'sync',
            // Missing ffta_member_code
        ]);

        $this->assertResponseRedirects(self::URL_MANUAL);
    }

    // ── Account Lock / Unlock ────────────────────────────────────────

    public function testLockRequiresAuthentication(): void
    {
        $client = self::createClient();
        /** @var LicenseeRepository $licenseeRepo */
        $licenseeRepo = static::getContainer()->get(LicenseeRepository::class);
        $licensee = $licenseeRepo->findOneBy([]);
        self::assertNotNull($licensee);

        $client->request(
            \Symfony\Component\HttpFoundation\Request::METHOD_POST,
            \sprintf('/licensee/%d/lock', $licensee->getId()),
        );

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testLockRequiresClubAdminRole(): void
    {
        $client = self::createLoggedInAsUserClient();
        /** @var LicenseeRepository $licenseeRepo */
        $licenseeRepo = static::getContainer()->get(LicenseeRepository::class);
        $licensee = $licenseeRepo->findOneBy([]);
        self::assertNotNull($licensee);

        $client->request(
            \Symfony\Component\HttpFoundation\Request::METHOD_POST,
            \sprintf('/licensee/%d/lock', $licensee->getId()),
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testLockSetsAccountLockedUntil(): void
    {
        $client = self::createLoggedInAsClubAdminClient();

        /** @var UserRepository $userRepo */
        $userRepo = static::getContainer()->get(UserRepository::class);
        /** @var LicenseeRepository $licenseeRepo */
        $licenseeRepo = static::getContainer()->get(LicenseeRepository::class);

        // Find a licensee that is NOT the club admin (avoid self-lock)
        $clubAdmin = $userRepo->findOneByEmail('clubadmin@ladg.com');
        self::assertNotNull($clubAdmin);
        $licensee = null;
        foreach ($licenseeRepo->findAll() as $l) {
            if ($l->getUser()?->getId() !== $clubAdmin->getId()) {
                $licensee = $l;
                break;
            }
        }
        self::assertNotNull($licensee, 'Could not find a non-admin licensee.');
        self::assertNotNull($licensee->getUser(), 'Licensee must have a user account.');

        $client->request(
            \Symfony\Component\HttpFoundation\Request::METHOD_POST,
            \sprintf('/licensee/%d/lock', $licensee->getId()),
        );

        $this->assertResponseRedirects(\sprintf('/licensee/%d', $licensee->getId()));

        // Refresh entity from DB
        $userRepo->getEntityManager()->clear();
        $updatedUser = $userRepo->find($licensee->getUser()->getId());
        self::assertNotNull($updatedUser?->getAccountLockedUntil());
    }

    public function testLockPreventsSelfLock(): void
    {
        $client = self::createLoggedInAsClubAdminClient();

        /** @var UserRepository $userRepo */
        $userRepo = static::getContainer()->get(UserRepository::class);
        /** @var LicenseeRepository $licenseeRepo */
        $licenseeRepo = static::getContainer()->get(LicenseeRepository::class);

        $clubAdmin = $userRepo->findOneByEmail('clubadmin@ladg.com');
        self::assertNotNull($clubAdmin);

        $licensee = null;
        foreach ($licenseeRepo->findAll() as $l) {
            if ($l->getUser()?->getId() === $clubAdmin->getId()) {
                $licensee = $l;
                break;
            }
        }
        self::assertNotNull($licensee, 'Club admin must have a linked licensee.');

        $client->request(
            \Symfony\Component\HttpFoundation\Request::METHOD_POST,
            \sprintf('/licensee/%d/lock', $licensee->getId()),
        );

        $this->assertResponseRedirects(\sprintf('/licensee/%d', $licensee->getId()));

        // Account must NOT be locked
        $userRepo->getEntityManager()->clear();
        $updatedUser = $userRepo->find($clubAdmin->getId());
        self::assertNull($updatedUser?->getAccountLockedUntil());
    }

    public function testUnlockClearsAccountLockedUntil(): void
    {
        $client = self::createLoggedInAsClubAdminClient();

        /** @var UserRepository $userRepo */
        $userRepo = static::getContainer()->get(UserRepository::class);
        /** @var LicenseeRepository $licenseeRepo */
        $licenseeRepo = static::getContainer()->get(LicenseeRepository::class);

        $clubAdmin = $userRepo->findOneByEmail('clubadmin@ladg.com');
        self::assertNotNull($clubAdmin);

        $licensee = null;
        foreach ($licenseeRepo->findAll() as $l) {
            if ($l->getUser()?->getId() !== $clubAdmin->getId()) {
                $licensee = $l;
                break;
            }
        }
        self::assertNotNull($licensee, 'Could not find a non-admin licensee.');
        self::assertNotNull($licensee->getUser());

        // Lock the account first
        $licensee->getUser()->lockPermanently();
        $userRepo->getEntityManager()->flush();

        $client->request(
            \Symfony\Component\HttpFoundation\Request::METHOD_POST,
            \sprintf('/licensee/%d/unlock', $licensee->getId()),
        );

        $this->assertResponseRedirects(\sprintf('/licensee/%d', $licensee->getId()));

        $userRepo->getEntityManager()->clear();
        $updatedUser = $userRepo->find($licensee->getUser()->getId());
        self::assertNull($updatedUser?->getAccountLockedUntil());
    }
}
