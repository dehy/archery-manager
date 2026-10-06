<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Licensee;
use App\Entity\Season;
use App\Repository\ClubRepository;
use App\Repository\LicenseeRepository;
use App\Repository\UserRepository;
use App\Tests\application\LoggedInTestCase;
use Symfony\Component\HttpFoundation\Request;

final class MemberManagementControllerTest extends LoggedInTestCase
{
    private const string URL_LIST = '/club/members';

    private const string URL_ACCOUNT = '/club/members/%d/account';

    public function testListRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, self::URL_LIST);

        $this->assertResponseRedirects();
    }

    public function testListIsForbiddenToRegularUsers(): void
    {
        self::createLoggedInAsUserClient()->request(Request::METHOD_GET, self::URL_LIST);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testListShowsClubMembersWithSharedAccountBadge(): void
    {
        $client = self::createLoggedInAsClubAdminClient();

        $crawler = $client->request(Request::METHOD_GET, self::URL_LIST);

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(10, $crawler->filter('tbody tr')->count());
        $this->assertGreaterThan(0, $crawler->filter('.badge:contains("partagé")')->count());
    }

    public function testListIsScopedToTheAdminsClub(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $foreign = $this->foreignClubLicensee();

        $client->request(Request::METHOD_GET, self::URL_LIST.'?q='.urlencode((string) $foreign->getLastname()));

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString($foreign->getUser()->getEmail(), (string) $client->getResponse()->getContent());
    }

    public function testSharedAccountFilterOnlyKeepsSharedAccounts(): void
    {
        $client = self::createLoggedInAsClubAdminClient();

        $crawler = $client->request(Request::METHOD_GET, self::URL_LIST.'?shared=1');

        $this->assertResponseIsSuccessful();
        $rows = $crawler->filter('tbody tr');
        $this->assertGreaterThan(0, $rows->count());
        $this->assertCount($rows->count(), $crawler->filter('tbody .badge:contains("partagé")'));
    }

    public function testSearchByEmail(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->clubLicensee();

        $crawler = $client->request(Request::METHOD_GET, self::URL_LIST.'?q='.urlencode((string) $licensee->getUser()->getEmail()));

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('tbody tr')->count());
        $this->assertStringContainsString($licensee->getUser()->getEmail(), $crawler->filter('tbody')->text());
    }

    public function testAccountFormIsForbiddenToRegularUsers(): void
    {
        $client = self::createLoggedInAsUserClient();
        $licensee = $this->clubLicensee();
        $client->request(Request::METHOD_GET, \sprintf(self::URL_ACCOUNT, $licensee->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAccountFormIsForbiddenForAnotherClubsLicensee(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $client->request(Request::METHOD_GET, \sprintf(self::URL_ACCOUNT, $this->foreignClubLicensee()->getId()));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCannotMoveTheLicenseeYouAreLoggedInWith(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $client->followRedirects();

        $own = self::getContainer()->get(LicenseeRepository::class)->findOneBy(['user' => self::getContainer()->get(UserRepository::class)->findOneByEmail('clubadmin@ladg.com')]);

        $client->request(Request::METHOD_GET, \sprintf(self::URL_ACCOUNT, $own->getId()));

        $this->assertSelectorTextContains('.alert-danger', 'connecté');
    }

    public function testMoveToNewAccountCreatesUserAndSendsInvitation(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->sharedAccountLicensee();
        $licenseeId = $licensee->getId();

        $client->request(Request::METHOD_GET, \sprintf(self::URL_ACCOUNT, $licenseeId));
        $this->assertResponseIsSuccessful();

        $client->submitForm('Changer de compte', [
            'licensee_account_change[destination]' => 'new',
            'licensee_account_change[email]' => 'grown.up@example.org',
        ]);

        $this->assertResponseRedirects(self::URL_LIST);
        $this->assertQueuedEmailCount(1);
        $this->assertEmailAddressContains($this->getMailerMessage(), 'to', 'grown.up@example.org');
        $moved = self::getContainer()->get(LicenseeRepository::class)->find($licenseeId);
        $this->assertSame('grown.up@example.org', $moved->getUser()->getEmail());
        $this->assertFalse($moved->getUser()->isIsVerified());
    }

    public function testMoveToNewAccountRejectsAnEmailAlreadyInUse(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->sharedAccountLicensee();

        $client->request(Request::METHOD_GET, \sprintf(self::URL_ACCOUNT, $licensee->getId()));
        $client->submitForm('Changer de compte', [
            'licensee_account_change[destination]' => 'new',
            'licensee_account_change[email]' => 'clubadmin@ladg.com',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback', 'déjà utilisée');
        $this->assertQueuedEmailCount(0);
    }

    public function testMoveToExistingAccountOfTheClub(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->sharedAccountLicensee();
        $licenseeId = $licensee->getId();
        $target = self::getContainer()->get(UserRepository::class)->findOneByEmail('clubadmin@ladg.com');

        $client->request(Request::METHOD_GET, \sprintf(self::URL_ACCOUNT, $licenseeId));
        $client->submitForm('Changer de compte', [
            'licensee_account_change[destination]' => 'existing',
            'licensee_account_change[email]' => 'clubadmin@ladg.com',
        ]);

        $this->assertResponseRedirects(self::URL_LIST);
        $this->assertQueuedEmailCount(0);
        $this->assertSame($target->getId(), self::getContainer()->get(LicenseeRepository::class)->find($licenseeId)->getUser()->getId());
    }

    public function testMoveToExistingAccountRejectsAccountsOutsideTheClub(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $licensee = $this->sharedAccountLicensee();
        $foreignEmail = $this->foreignClubLicensee()->getUser()->getEmail();

        $client->request(Request::METHOD_GET, \sprintf(self::URL_ACCOUNT, $licensee->getId()));
        $client->submitForm('Changer de compte', [
            'licensee_account_change[destination]' => 'existing',
            'licensee_account_change[email]' => $foreignEmail,
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.invalid-feedback', 'Aucun compte de votre club');
    }

    private function clubLicensee(): Licensee
    {
        return $this->licensees()[0];
    }

    private function sharedAccountLicensee(): Licensee
    {
        $shared = self::getContainer()->get(LicenseeRepository::class)->findForMemberManagement(
            $this->club(),
            $this->season(),
            sharedAccountOnly: true,
        );
        $this->assertNotEmpty($shared, 'Fixtures should contain shared accounts.');

        return $shared[0];
    }

    private function foreignClubLicensee(): Licensee
    {
        $foreign = self::getContainer()->get(ClubRepository::class)->findOneBy(['name' => 'Les Archers du Bosquet']);

        return self::getContainer()->get(LicenseeRepository::class)->findForMemberManagement($foreign, $this->season())[0];
    }

    /**
     * @return list<Licensee>
     */
    private function licensees(): array
    {
        return self::getContainer()->get(LicenseeRepository::class)->findForMemberManagement($this->club(), $this->season());
    }

    private function club(): \App\Entity\Club
    {
        return self::getContainer()->get(ClubRepository::class)->findOneBy(['name' => 'Les Archers de Guyenne']);
    }

    private function season(): int
    {
        return Season::seasonForDate(new \DateTimeImmutable());
    }
}
