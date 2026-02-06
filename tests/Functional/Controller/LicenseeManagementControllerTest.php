<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Season;
use App\Tests\application\LoggedInTestCase;

class LicenseeManagementControllerTest extends LoggedInTestCase
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
        $client = static::createClient();
        $client->request('GET', self::URL_CHOICE);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    public function testNewChoicePageRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request('GET', self::URL_CHOICE);

        $this->assertResponseIsSuccessful();
    }

    public function testNewChoicePostSyncRedirects(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request('POST', self::URL_CHOICE, [
            'choice' => 'sync',
            'ffta_member_code' => '123456A',
        ]);

        $this->assertResponseRedirects('/licensees/manage/new/sync/123456A');
    }

    public function testNewChoicePostManualRedirects(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request('POST', self::URL_CHOICE, [
            'choice' => 'manual',
        ]);

        $this->assertResponseRedirects(self::URL_MANUAL);
    }

    public function testNewManualInitializesSessionAndRedirects(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request('GET', self::URL_MANUAL);

        $this->assertResponseRedirects(self::URL_STEP1);
    }

    public function testStep1RequiresSessionData(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request('GET', self::URL_STEP1);

        $this->assertResponseRedirects(self::URL_CHOICE);
    }

    public function testStep1RendersFormAfterManualInit(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request('GET', self::URL_MANUAL);
        $client->followRedirect();

        $this->assertResponseIsSuccessful();
    }

    public function testStep2RequiresStep1Data(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // Init session via manual, then access step2 without licensee data
        $client->request('GET', self::URL_MANUAL);
        $client->request('GET', self::URL_STEP2);

        $this->assertResponseRedirects(self::URL_STEP1);
    }

    public function testStep3RequiresStep2Data(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request('GET', self::URL_MANUAL);
        $client->followRedirect();
        $client->request('GET', self::URL_STEP3);

        $this->assertResponseRedirects(self::URL_STEP2);
    }

    public function testStep4RequiresStep3Data(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request('GET', self::URL_MANUAL);
        $client->followRedirect();
        $client->request('GET', self::URL_STEP4);

        $this->assertResponseRedirects(self::URL_STEP3);
    }

    public function testCancelClearsSessionAndRedirects(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request('GET', self::URL_MANUAL);
        $client->request('GET', self::URL_CANCEL);

        $this->assertResponseRedirects('/licensees');
    }

    public function testStep2RendersFormWithValidSessionData(): void
    {
        $client = self::createLoggedInAsAdminClient();

        $client->request('GET', self::URL_MANUAL);
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
        $client->request('GET', self::URL_MANUAL);
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

        $client->request('GET', '/licensees/manage/new/sync/INVALID1');

        $this->assertResponseRedirects(self::URL_CHOICE);
    }

    public function testUserRoleCannotAccessManagement(): void
    {
        $client = self::createLoggedInAsUserClient();

        $client->request('GET', self::URL_CHOICE);

        $this->assertResponseStatusCodeSame(403);
    }
}
