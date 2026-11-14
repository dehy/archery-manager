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
 * Mon compte in the redesigned interface (user/account_next.html.twig).
 */
final class AccountNextTest extends LoggedInTestCase
{
    public function testTodaysInterfaceKeepsItsAccountPage(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(Request::METHOD_GET, '/my-account');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('#am-account-login-title');
    }

    public function testAMemberSeesTheirLoginArchersDisplayAndCalendarLinks(): void
    {
        $client = self::createLoggedInAsUserClient();
        $user = $this->enableNewInterface($client);

        $client->request(Request::METHOD_GET, '/my-account');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Mon compte');
        $this->assertSelectorTextContains('#am-account-login-title', 'Connexion');
        $this->assertAnySelectorTextContains('.am-facts dd', (string) $user->getEmail());
        $this->assertSelectorTextContains('#am-account-archers-title', 'Archers du compte');
        // The member's account holds two archers: the current one is selected, the other can be switched to
        $this->assertSelectorTextContains('.am-agenda .am-status-ok', 'Sélectionné');
        $this->assertSelectorExists('.am-main a[href*="_switch_licensee="]');
        $this->assertSelectorExists('.am-main a[href="/my-account/appearance"]');
        $this->assertSelectorExists('form[action="/my-account/interface"]');
        $this->assertSelectorCount($user->getLicensees()->count(), '.am-calendar-feed');
    }

    public function testACalendarLinkIsCreatedFromThePage(): void
    {
        $client = self::createLoggedInAsUserClient();
        $user = $this->enableNewInterface($client);
        $licensee = $user->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);
        $licensee->revokeCalendarToken();
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $crawler = $client->request(Request::METHOD_GET, '/my-account');
        $client->submit($crawler->filter(\sprintf('form[action="/my-account/calendar/%d/generate-token"]', $licensee->getId()))->form());

        $this->assertResponseRedirects('/my-account');
        $client->followRedirect();
        $this->assertSelectorExists(\sprintf('#am-feed-%d', $licensee->getId()));
    }

    private function enableNewInterface(KernelBrowser $client): User
    {
        /** @var User $sessionUser */
        $sessionUser = $client->getContainer()->get('security.token_storage')->getToken()?->getUser()
            ?? throw new \LogicException('Not logged in');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->find(User::class, $sessionUser->getId());
        $this->assertInstanceOf(User::class, $user);

        $user->setNewInterface(true);
        $entityManager->flush();

        return $user;
    }
}
