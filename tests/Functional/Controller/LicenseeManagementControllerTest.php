<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Licensee;
use App\Entity\User;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use App\Tests\application\LoggedInTestCase;

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

    // ── Move User — Step 1 ────────────────────────────────────────────

    public function testMoveUserStep1RequiresAuthentication(): void
    {
        $client = self::createClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testMoveUserStep1DeniedForRegularUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testMoveUserStep1DeniedWhenAdminBelongsToDifferentClub(): void
    {
        // admin@acme.org belongs to club_ladb; licensee_ladg_1 belongs to club_ladg
        $client = self::createLoggedInAsAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));

        // Redirects to the licensee's profile with a danger flash
        $this->assertResponseRedirects();
        $location = (string) $client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/licensee/'.$licensee->getId(), $location);
    }

    public function testMoveUserStep1RendersFormForValidClubAdmin(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));

        $this->assertResponseIsSuccessful();
    }

    public function testMoveUserStep1PostNewWithEmptyEmailShowsError(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));

        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => '',
        ]);
        $client->submit($form);

        // Stays on form with validation errors (422 Unprocessable Content)
        $this->assertResponseStatusCodeSame(422);
    }

    public function testMoveUserStep1PostNewWithInvalidEmailFormatShowsError(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));

        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => 'not-a-valid-email',
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testMoveUserStep1PostNewWithAlreadyUsedEmailShowsError(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));

        // user2@ladg.com already exists in fixtures
        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => 'user2@ladg.com',
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testMoveUserStep1PostNewWithFreshEmailRedirectsToStep2(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));

        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => 'brand-new-'.uniqid().'@example.com',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects($this->moveStep2Url($licensee->getId()));
    }

    public function testMoveUserStep1PostExistingWithNoUserShowsError(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));

        // Choose 'existing' but leave existing_user empty
        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'existing',
            'form[existing_user]' => '',
        ]);
        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testMoveUserStep1PostExistingWithValidUserRedirectsToStep2(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));

        // Pick any valid option from the select (exclude empty placeholder)
        $selectField = $crawler->filter('select[name="form[existing_user]"]');
        $this->assertGreaterThan(0, $selectField->count(), 'existing_user select must exist');
        $options = $selectField->filter('option[value!=""]');
        $this->assertGreaterThan(0, $options->count(), 'At least one selectable user must exist');
        $targetUserId = $options->first()->attr('value');

        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'existing',
            'form[existing_user]' => $targetUserId,
        ]);
        $client->submit($form);

        $this->assertResponseRedirects($this->moveStep2Url($licensee->getId()));
    }

    // ── Move User — Step 2 ────────────────────────────────────────────

    public function testMoveUserStep2WithoutSessionRedirectsToStep1(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep2Url($licensee->getId()));

        $this->assertResponseRedirects($this->moveStep1Url($licensee->getId()));
    }

    public function testMoveUserStep2WithNewChoiceRendersConfirmation(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');

        // Navigate step1 → step2
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));
        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => 'step2-test-'.uniqid().'@example.com',
        ]);
        $client->submit($form);
        $client->followRedirect();

        $this->assertResponseIsSuccessful();
    }

    public function testMoveUserStep2WithExistingChoiceRendersConfirmation(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));
        $selectField = $crawler->filter('select[name="form[existing_user]"]');
        $targetUserId = $selectField->filter('option[value!=""]')->first()->attr('value');

        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'existing',
            'form[existing_user]' => $targetUserId,
        ]);
        $client->submit($form);
        $client->followRedirect();

        $this->assertResponseIsSuccessful();
    }

    public function testMoveUserStep2PostNewChoiceCreatesUserAndRedirects(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $newEmail = 'created-user-'.uniqid().'@example.com';

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));
        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => $newEmail,
        ]);
        $client->submit($form);

        $crawler = $client->followRedirect();
        $form = $crawler->selectButton('Confirmer le déplacement')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/licensee/'.$licensee->getId());

        // Verify the new user was created
        $userRepo = self::getContainer()->get(UserRepository::class);
        $createdUser = $userRepo->findOneByEmail($newEmail);
        $this->assertInstanceOf(User::class, $createdUser);
    }

    public function testMoveUserStep2PostExistingChoiceRelinksLicenseeAndRedirects(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));
        $targetUserId = $crawler->filter('select[name="form[existing_user]"]')->filter('option[value!=""]')->first()->attr('value');

        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'existing',
            'form[existing_user]' => $targetUserId,
        ]);
        $client->submit($form);

        $crawler = $client->followRedirect();
        $form = $crawler->selectButton('Confirmer le déplacement')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/licensee/'.$licensee->getId());

        // Verify the licensee is now linked to the target user
        $licenseeRepo = self::getContainer()->get(LicenseeRepository::class);
        $updatedLicensee = $licenseeRepo->find($licensee->getId());
        $this->assertSame((int) $targetUserId, $updatedLicensee?->getUser()?->getId());
    }

    public function testMoveUserStep2DeletesSourceUserWhenItWasTheLastLicensee(): void
    {
        // user1@ladg.com has exactly one licensee (licensee_ladg_1)
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->getLicenseeByUserEmail('user1@ladg.com');
        $sourceUserId = $licensee->getUser()?->getId();
        $this->assertNotNull($sourceUserId, 'Fixture user must exist');
        $newEmail = 'delete-source-'.uniqid().'@example.com';

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($licensee->getId()));
        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => $newEmail,
        ]);
        $client->submit($form);

        $crawler = $client->followRedirect();
        $form = $crawler->selectButton('Confirmer le déplacement')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/licensee/'.$licensee->getId());

        // The source user should have been deleted (had only 1 licensee)
        $userRepo = self::getContainer()->get(UserRepository::class);
        $this->assertNull($userRepo->find($sourceUserId));
    }

    public function testMoveUserStep2KeepsSourceUserWhenItHasMultipleLicensees(): void
    {
        // parent1@ladg.com has two licensees: licensee_ladg_parent_1 + licensee_ladg_child_1
        $client = self::createLoggedInAsClubAdminClient();

        // Move the child licensee (also linked to parent1's user)
        $childLicensee = $this->getLicenseeByUserEmail('parent1@ladg.com', second: true);
        $sourceUserId = $childLicensee->getUser()?->getId();
        $this->assertNotNull($sourceUserId, 'Source user must exist');
        $newEmail = 'keep-source-'.uniqid().'@example.com';

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, $this->moveStep1Url($childLicensee->getId()));
        $form = $crawler->selectButton('Suivant — Confirmer')->form([
            'form[user_choice]' => 'new',
            'form[email]' => $newEmail,
        ]);
        $client->submit($form);

        $crawler = $client->followRedirect();
        $form = $crawler->selectButton('Confirmer le déplacement')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/licensee/'.$childLicensee->getId());

        // The source user should still exist (has another licensee)
        $userRepo = self::getContainer()->get(UserRepository::class);
        $this->assertInstanceOf(User::class, $userRepo->find($sourceUserId));
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * Returns the nth licensee (0-indexed) associated with the given user email.
     * Pass second: true to get the second licensee (index 1).
     */
    private function getLicenseeByUserEmail(string $email, bool $second = false): Licensee
    {
        $userRepo = self::getContainer()->get(UserRepository::class);
        $licenseeRepo = self::getContainer()->get(LicenseeRepository::class);

        $user = $userRepo->findOneByEmail($email);
        $this->assertInstanceOf(User::class, $user, \sprintf("User fixture '%s' not found.", $email));

        $licensees = $licenseeRepo->findBy(['user' => $user]);
        $this->assertNotEmpty($licensees, \sprintf("No licensees found for user '%s'.", $email));

        $index = $second ? 1 : 0;
        $this->assertArrayHasKey($index, $licensees, \sprintf("Licensee index %s not found for user '%s'.", $index, $email));

        return $licensees[$index];
    }

    private function moveStep1Url(int $licenseeId): string
    {
        return \sprintf('/licensees/manage/%d/move-user/step1', $licenseeId);
    }

    private function moveStep2Url(int $licenseeId): string
    {
        return \sprintf('/licensees/manage/%d/move-user/step2', $licenseeId);
    }
}
