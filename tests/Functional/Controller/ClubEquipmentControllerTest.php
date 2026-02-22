<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\DBAL\Types\ClubEquipmentType as ClubEquipmentTypeEnum;
use App\Entity\ClubEquipment;
use App\Entity\Licensee;
use App\Repository\ClubEquipmentRepository;
use App\Repository\LicenseeRepository;
use App\Tests\application\LoggedInTestCase;

final class ClubEquipmentControllerTest extends LoggedInTestCase
{
    private const string URL_INDEX = '/club-equipment';

    private const string URL_NEW = '/club-equipment/new';

    private const string URL_LOANS = '/club-equipment/loans';

    private const string URL_EQUIPMENT = '/club-equipment/';

    // ── Index ──────────────────────────────────────────────────────────

    public function testIndexRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_INDEX);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testIndexDeniedForRegularUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_INDEX);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_INDEX);

        $this->assertResponseIsSuccessful();
    }

    // ── New Equipment ──────────────────────────────────────────────────

    public function testNewEquipmentFormRenders(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_NEW);

        $this->assertResponseIsSuccessful();
    }

    public function testNewEquipmentSubmitOtherType(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_NEW);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form();
        $form['club_equipment[type]'] = ClubEquipmentTypeEnum::OTHER;
        $form['club_equipment[name]'] = 'Test Quiver #1';
        $form['club_equipment[quantity]'] = '5';
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_INDEX);
    }

    public function testNewEquipmentDeniedForRegularUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_NEW);

        $this->assertResponseStatusCodeSame(403);
    }

    // ── Show ───────────────────────────────────────────────────────────

    public function testShowEquipmentRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EQUIPMENT.$equipmentId);
        $this->assertResponseIsSuccessful();
    }

    // ── Edit ───────────────────────────────────────────────────────────

    public function testEditEquipmentFormRenders(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EQUIPMENT.$equipmentId.'/edit');
        $this->assertResponseIsSuccessful();
    }

    public function testEditEquipmentSubmit(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EQUIPMENT.$equipmentId.'/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form();
        $form['club_equipment[name]'] = 'Updated Equipment Name';
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_EQUIPMENT.$equipmentId);
    }

    // ── Delete ─────────────────────────────────────────────────────────

    public function testDeleteEquipmentRedirects(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_EQUIPMENT.$equipmentId.'/delete');
        $this->assertResponseRedirects(self::URL_INDEX);
    }

    public function testDeleteRequiresPostMethod(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EQUIPMENT.$equipmentId.'/delete');
        $this->assertResponseStatusCodeSame(405);
    }

    // ── Loan ───────────────────────────────────────────────────────────

    public function testLoanFormRenders(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EQUIPMENT.$equipmentId.'/loan');
        $this->assertResponseIsSuccessful();
    }

    // ── Return ─────────────────────────────────────────────────────────

    public function testReturnEquipmentNotLoanedShowsError(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_EQUIPMENT.$equipmentId.'/return');

        // Should redirect since equipment is not currently loaned
        $this->assertResponseRedirects(self::URL_EQUIPMENT.$equipmentId);
    }

    public function testReturnRequiresPostMethod(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EQUIPMENT.$equipmentId.'/return');
        $this->assertResponseStatusCodeSame(405);
    }

    // ── Loans List ────────────────────────────────────────────────────

    public function testLoansListRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LOANS);

        $this->assertResponseIsSuccessful();
    }

    public function testLoansListDeniedForRegularUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_LOANS);

        $this->assertResponseStatusCodeSame(403);
    }

    // ── Helper ─────────────────────────────────────────────────────────

    /**
     * Create a test equipment via the form and return its ID.
     */
    private function createTestEquipment(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): int
    {
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_NEW);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form();
        $form['club_equipment[type]'] = ClubEquipmentTypeEnum::OTHER;
        $form['club_equipment[name]'] = 'Test Equipment '.uniqid();
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_INDEX);

        // Find the created equipment
        $equipmentRepo = self::getContainer()->get(ClubEquipmentRepository::class);
        $allEquipment = $equipmentRepo->findAll();
        $lastEquipment = end($allEquipment);

        $this->assertInstanceOf(ClubEquipment::class, $lastEquipment);

        return $lastEquipment->getId();
    }

    // ── Full Loan & Return Flow ───────────────────────────────────────

    public function testLoanSubmitAndReturnFlow(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        // Get a licensee to loan to
        $licenseeRepo = self::getContainer()->get(LicenseeRepository::class);
        $allLicensees = $licenseeRepo->findAll();
        $this->assertNotEmpty($allLicensees);
        $licensee = $allLicensees[0];

        // Submit loan form
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EQUIPMENT.$equipmentId.'/loan');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Confirmer le prêt')->form();
        $form['equipment_loan[borrower]'] = $licensee->getId();
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_EQUIPMENT.$equipmentId);

        // Equipment is now loaned - loaning again should redirect with error
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EQUIPMENT.$equipmentId.'/loan');
        $this->assertResponseRedirects(self::URL_EQUIPMENT.$equipmentId);

        // Return the equipment
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_EQUIPMENT.$equipmentId.'/return');
        $this->assertResponseRedirects(self::URL_EQUIPMENT.$equipmentId);
    }

    public function testDeleteLoanedEquipmentShowsError(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        // Loan the equipment
        $licenseeRepo = self::getContainer()->get(LicenseeRepository::class);
        $allLicensees = $licenseeRepo->findAll();
        $licensee = $allLicensees[0];

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EQUIPMENT.$equipmentId.'/loan');
        $form = $crawler->selectButton('Confirmer le prêt')->form();
        $form['equipment_loan[borrower]'] = $licensee->getId();
        $client->submit($form);

        // Try to delete - should redirect with error
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_EQUIPMENT.$equipmentId.'/delete');
        $this->assertResponseRedirects(self::URL_EQUIPMENT.$equipmentId);

        // Return it so it can be cleaned up
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_POST, self::URL_EQUIPMENT.$equipmentId.'/return');
    }

    // ── New Equipment with Bow Type ───────────────────────────────────

    public function testNewEquipmentSubmitBowType(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_NEW);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form();
        $form['club_equipment[type]'] = ClubEquipmentTypeEnum::BOW;
        $form['club_equipment[name]'] = 'Test Bow '.uniqid();
        $form['club_equipment[quantity]'] = '3';
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_INDEX);
    }

    // ── Show non-existent equipment ───────────────────────────────────

    public function testShowNonExistentEquipmentReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EQUIPMENT.'99999');

        $this->assertResponseStatusCodeSame(404);
    }

    // ── Edit non-existent equipment ───────────────────────────────────

    public function testEditNonExistentEquipmentReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_EQUIPMENT.'99999/edit');

        $this->assertResponseStatusCodeSame(404);
    }
}
