<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Licensee;
use App\Entity\User;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

/**
 * The archer profile (licensee/show_next.html.twig) and the "Plus" menu (mobile/other_menu_next.html.twig) of the
 * redesigned interface.
 */
final class ProfileNextTest extends LoggedInTestCase
{
    public function testTodaysInterfaceKeepsItsProfileAndMenu(): void
    {
        $client = self::createLoggedInAsUserClient();

        $client->request(Request::METHOD_GET, '/my-profile');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.am-profile-head');

        $client->request(Request::METHOD_GET, '/other');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.am-menu-heading');
    }

    public function testAMemberSeesTheirOwnProfile(): void
    {
        $client = self::createLoggedInAsUserClient();
        $user = $this->enableNewInterface($client);
        $licensee = $user->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        $client->request(Request::METHOD_GET, '/my-profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $licensee->getFullname());
        $this->assertSelectorTextContains('#am-license-title', 'Ma licence');
        $this->assertSelectorTextContains('.am-profile-head .am-status', 'Licence');
        $this->assertSelectorTextContains('#am-results-title', 'Résultats');
        $this->assertSelectorExists('.am-main a[href="/my-account/appearance"]');
        $this->assertSelectorExists(\sprintf('a[href="/licensee/%d/edit"]', $licensee->getId()));
    }

    public function testACoachSeesAMemberWithoutTheirPersonalAdvice(): void
    {
        $client = self::createLoggedInAsCoachClient();
        $this->enableNewInterface($client);
        $member = $this->otherMemberOfTheClub($client);

        $client->request(Request::METHOD_GET, \sprintf('/licensee/%d', $member->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('#am-license-title', 'Licence');
        // Advice is personal: only the archer's own account reads it
        $this->assertSelectorNotExists('#am-advice-title');
        // Appearance is the viewer's own setting: not offered on somebody else's profile (the sidebar menu still has it)
        $this->assertSelectorNotExists('.am-main a[href="/my-account/appearance"]');
    }

    public function testThePlusMenuGroupsWhatTheTabBarLeavesOut(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $client->request(Request::METHOD_GET, '/other');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Plus');
        $this->assertSelectorTextContains('#am-plus-archer', 'Archer et saison');
        $this->assertSelectorTextContains('#am-plus-club', 'Club');
        $this->assertSelectorNotExists('#am-plus-management');
        $this->assertSelectorExists('a[href="/my-account/appearance"]');
        $this->assertSelectorExists('a[href="/logout"]');
        // The member's account holds two archers: the other one is offered
        $this->assertSelectorExists('a[href*="_switch_licensee="]');
    }

    public function testThePlusMenuShowsTheManagementToClubAdmins(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $this->enableNewInterface($client);

        $client->request(Request::METHOD_GET, '/other');

        $this->assertSelectorTextContains('#am-plus-management', 'Gestion');
        $this->assertSelectorExists('a[href="/club-application/manage"]');
    }

    private function otherMemberOfTheClub(KernelBrowser $client): Licensee
    {
        $user = $this->currentUser($client);
        $season = \App\Entity\Season::seasonForDate(new \DateTimeImmutable());
        $club = $user->getLicensees()->first()?->getLicenseForSeason($season)?->getClub();
        $this->assertInstanceOf(\App\Entity\Club::class, $club);

        foreach (self::getContainer()->get(EntityManagerInterface::class)->getRepository(Licensee::class)->findAll() as $licensee) {
            if (!$user->getLicensees()->contains($licensee) && $licensee->getLicenseForSeason($season)?->getClub() === $club) {
                return $licensee;
            }
        }

        $this->fail("No other member in the coach's club");
    }

    private function enableNewInterface(KernelBrowser $client): User
    {
        $user = $this->currentUser($client);
        $user->setNewInterface(true);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        return $user;
    }

    private function currentUser(KernelBrowser $client): User
    {
        /** @var User $sessionUser */
        $sessionUser = $client->getContainer()->get('security.token_storage')->getToken()?->getUser()
            ?? throw new \LogicException('Not logged in');

        $user = self::getContainer()->get(EntityManagerInterface::class)->find(User::class, $sessionUser->getId());
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }
}
