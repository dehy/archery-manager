<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseAgeCategoryType;
use App\DBAL\Types\LicenseCategoryType;
use App\DBAL\Types\LicenseType;
use App\DataFixtures\Faker\Provider\FftaCodeProvider;
use App\Entity\License;
use App\Entity\Licensee;
use App\Repository\ClubRepository;
use App\Repository\LicenseeRepository;
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

    private const string URL_RENEW_PREFIX = '/licensees/manage/renew/';

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

    public function testNewChoicePostWithUnknownCodeShowsWarningWithCreateCta(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_CHOICE, [
            'ffta_member_code' => 'UNKNOWN1',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-warning', 'UNKNOWN1');

        $ctaLink = $crawler->selectLink('Créer ce licencié');
        $this->assertGreaterThan(0, $ctaLink->count());
        $this->assertStringContainsString(
            '/licensees/manage/new/manual?ffta_member_code=UNKNOWN1',
            (string) $ctaLink->attr('href'),
        );
    }

    public function testManualCreationPrefillsSearchedCode(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MANUAL.'?ffta_member_code=unknown1');

        $crawler = $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSame('UNKNOWN1', $crawler->filter('#licensee_form_fftaMemberCode')->attr('value'));
    }

    public function testNewChoicePostWithEmptyCodeRedirectsToManual(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_CHOICE, [
            'ffta_member_code' => '',
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

    public function testClubAdminChoicePostWithEmptyCodeRedirectsToManual(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_CHOICE, [
            'ffta_member_code' => '',
        ]);

        $this->assertResponseRedirects(self::URL_MANUAL);
    }

    public function testClubAdminChoicePostWithOwnClubLicenseeRedirectsToRenew(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->createLicenseeWithPastSeasonLicense('club_ladg');

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_CHOICE, [
            'ffta_member_code' => $licensee->getFftaMemberCode(),
        ]);

        $this->assertResponseRedirects(self::URL_RENEW_PREFIX.$licensee->getId());
    }

    public function testClubAdminChoicePostNormalizesLowercaseCodeBeforeLookup(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->createLicenseeWithPastSeasonLicense('club_ladg');

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_CHOICE, [
            'ffta_member_code' => strtolower((string) $licensee->getFftaMemberCode()),
        ]);

        $this->assertResponseRedirects(self::URL_RENEW_PREFIX.$licensee->getId());
    }

    public function testClubAdminChoicePostWithForeignClubLicenseeShowsMinimalInfoOnly(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->createLicenseeWithPastSeasonLicense('club_ladb');

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_CHOICE, [
            'ffta_member_code' => $licensee->getFftaMemberCode(),
        ]);

        $this->assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString($licensee->getLastname(), $content);
        $this->assertStringNotContainsString((string) $licensee->getFftaMemberCode(), $content);
    }

    public function testRenewDeniedForClubAdminOfAnotherClub(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->createLicenseeWithPastSeasonLicense('club_ladb');

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_RENEW_PREFIX.$licensee->getId());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRenewCreatesLicenseForCurrentSeason(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->createLicenseeWithPastSeasonLicense('club_ladg');

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_RENEW_PREFIX.$licensee->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer la licence')->form([
            'license_form[type]' => 'A',
            'license_form[category]' => 'A',
            'license_form[ageCategory]' => 'S1',
        ]);
        $activityCheckboxes = $crawler->filter('input[name="license_form[activities][]"]');
        if ($activityCheckboxes->count() > 0) {
            $form['license_form[activities]'] = [$activityCheckboxes->first()->attr('value')];
        }

        $client->submit($form);

        $this->assertResponseRedirects('/licensee/'.$licensee->getId());

        /** @var LicenseeRepository $licenseeRepository */
        $licenseeRepository = self::getContainer()->get(LicenseeRepository::class);
        $reloaded = $licenseeRepository->find($licensee->getId());
        $this->assertInstanceOf(Licensee::class, $reloaded);
        $this->assertInstanceOf(License::class, $reloaded->getLicenseForSeason(2026));
    }

    public function testRenewRedirectsWithFlashWhenSeasonLicenseAlreadyExists(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->createLicenseeWithPastSeasonLicense('club_ladg');

        // Give it a 2026 license too, so it already has one for the current season.
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $currentSeasonLicense = new License();
        $currentSeasonLicense->setLicensee($licensee);
        $currentSeasonLicense->setClub($licensee->getMostRecentLicense()->getClub());
        $currentSeasonLicense->setSeason(2026);
        $currentSeasonLicense->setType(LicenseType::ADULTES_COMPETITION);
        $currentSeasonLicense->setCategory(LicenseCategoryType::ADULTES);
        $currentSeasonLicense->setAgeCategory(LicenseAgeCategoryType::SENIOR_1);
        $currentSeasonLicense->setActivities([LicenseActivityType::CL]);

        $em->persist($currentSeasonLicense);
        $em->flush();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_RENEW_PREFIX.$licensee->getId());

        $this->assertResponseRedirects('/licensee/'.$licensee->getId());
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
            // Missing ffta_member_code
        ]);

        $this->assertResponseRedirects(self::URL_MANUAL);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    /**
     * Persist a Licensee with a single License for a past season (2025) at the
     * given club fixture reference, so it's a renewal candidate for the
     * currently selected season (2026) without conflicting with fixture data.
     */
    private function createLicenseeWithPastSeasonLicense(string $clubReference): Licensee
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        /** @var ClubRepository $clubRepository */
        $clubRepository = self::getContainer()->get(ClubRepository::class);
        $club = 'club_ladg' === $clubReference
            ? $clubRepository->findOneByCode('1033093')
            : $clubRepository->findOneByCode('1033078');
        $this->assertInstanceOf(\App\Entity\Club::class, $club, \sprintf('Club fixture "%s" not found.', $clubReference));

        $uniqueId = uniqid();

        $licensee = new Licensee();
        $licensee->setFirstname('Renew');
        $licensee->setLastname('Candidate'.$uniqueId);
        $licensee->setGender('M');
        $licensee->setBirthdate(new \DateTime('1990-01-01'));

        $fftaId = FftaCodeProvider::fftaId();
        $licensee->setFftaMemberCode(FftaCodeProvider::fftaCode($fftaId));
        $licensee->setFftaId($fftaId);

        /** @var \App\Repository\UserRepository $userRepository */
        $userRepository = self::getContainer()->get(\App\Repository\UserRepository::class);
        $user = $userRepository->findOneByEmail('user1@ladg.com');
        $this->assertInstanceOf(\App\Entity\User::class, $user);
        $licensee->setUser($user);

        $license = new License();
        $license->setLicensee($licensee);
        $license->setClub($club);
        $license->setSeason(2025);
        $license->setType(LicenseType::ADULTES_COMPETITION);
        $license->setCategory(LicenseCategoryType::ADULTES);
        $license->setAgeCategory(LicenseAgeCategoryType::SENIOR_1);
        $license->setActivities([LicenseActivityType::CL]);

        $em->persist($licensee);
        $em->persist($license);
        $em->flush();

        return $licensee;
    }
}
