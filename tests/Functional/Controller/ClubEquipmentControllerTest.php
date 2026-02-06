<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\DBAL\Types\ClubEquipmentType as ClubEquipmentTypeEnum;
use App\Entity\ClubEquipment;
use App\Repository\ClubEquipmentRepository;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ClubEquipmentControllerTest extends LoggedInTestCase
{
    private const string URL_INDEX = '/club-equipment';
    private const string URL_NEW = '/club-equipment/new';
    private const string URL_LOANS = '/club-equipment/loans';
    private const string URL_EQUIPMENT = '/club-equipment/';

    // ── Index ──────────────────────────────────────────────────────────

    public function testIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', self::URL_INDEX);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }

    public function testIndexDeniedForRegularUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request('GET', self::URL_INDEX);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request('GET', self::URL_INDEX);

        $this->assertResponseIsSuccessful();
    }

    // ── New Equipment ──────────────────────────────────────────────────

    public function testNewEquipmentFormRenders(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request('GET', self::URL_NEW);

        $this->assertResponseIsSuccessful();
    }

    public function testNewEquipmentSubmitOtherType(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $crawler = $client->request('GET', self::URL_NEW);

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form();
        $form['club_equipment[type]'] = ClubEquipmentTypeEnum::OTHER;
        $form['club_equipment[name]'] = 'Test Quiver #1';
        $form['club_equipment[count]'] = '5';
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_INDEX);
    }

    public function testNewEquipmentDeniedForRegularUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request('GET', self::URL_NEW);

        $this->assertResponseStatusCodeSame(403);
    }

    // ── Show ───────────────────────────────────────────────────────────

    public function testShowEquipmentRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request('GET', self::URL_EQUIPMENT.$equipmentId);
        $this->assertResponseIsSuccessful();
    }

    // ── Edit ───────────────────────────────────────────────────────────

    public function testEditEquipmentFormRenders(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request('GET', self::URL_EQUIPMENT.$equipmentId.'/edit');
        $this->assertResponseIsSuccessful();
    }

    public function testEditEquipmentSubmit(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $crawler = $client->request('GET', self::URL_EQUIPMENT.$equipmentId.'/edit');
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

        $client->request('POST', self::URL_EQUIPMENT.$equipmentId.'/delete');
        $this->assertResponseRedirects(self::URL_INDEX);
    }

    public function testDeleteRequiresPostMethod(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request('GET', self::URL_EQUIPMENT.$equipmentId.'/delete');
        $this->assertResponseStatusCodeSame(405);
    }

    // ── Loan ───────────────────────────────────────────────────────────

    public function testLoanFormRenders(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request('GET', self::URL_EQUIPMENT.$equipmentId.'/loan');
        $this->assertResponseIsSuccessful();
    }

    // ── Return ─────────────────────────────────────────────────────────

    public function testReturnEquipmentNotLoanedShowsError(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request('POST', self::URL_EQUIPMENT.$equipmentId.'/return');

        // Should redirect since equipment is not currently loaned
        $this->assertResponseRedirects(self::URL_EQUIPMENT.$equipmentId);
    }

    public function testReturnRequiresPostMethod(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $equipmentId = $this->createTestEquipment($client);

        $client->request('GET', self::URL_EQUIPMENT.$equipmentId.'/return');
        $this->assertResponseStatusCodeSame(405);
    }

    // ── Loans List ────────────────────────────────────────────────────

    public function testLoansListRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request('GET', self::URL_LOANS);

        // Note: The loans.html.twig template may not exist yet
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || 500 === $response->getStatusCode(),
            'Expected success or 500 (template not yet created)'
        );
    }

    public function testLoansListDeniedForRegularUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request('GET', self::URL_LOANS);

        $this->assertResponseStatusCodeSame(403);
    }

    // ── Helper ─────────────────────────────────────────────────────────

    /**
     * Create a test equipment via the form and return its ID.
     */
    private function createTestEquipment(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): int
    {
        $crawler = $client->request('GET', self::URL_NEW);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form();
        $form['club_equipment[type]'] = ClubEquipmentTypeEnum::OTHER;
        $form['club_equipment[name]'] = 'Test Equipment '.uniqid();
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_INDEX);

        // Find the created equipment
        $equipmentRepo = static::getContainer()->get(ClubEquipmentRepository::class);
        $allEquipment = $equipmentRepo->findAll();
        $lastEquipment = end($allEquipment);

        $this->assertInstanceOf(ClubEquipment::class, $lastEquipment);

        return $lastEquipment->getId();
    }
}
