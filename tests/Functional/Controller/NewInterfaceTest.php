<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

/**
 * The per-user switch to the redesigned interface (Mon compte) and the shell it turns on (base_next.html.twig).
 */
final class NewInterfaceTest extends LoggedInTestCase
{
    private const string URL_MY_ACCOUNT = '/my-account';

    private const string URL_TOGGLE = '/my-account/interface';

    private const string BUTTON_TRY = 'Essayer la nouvelle interface';

    private const string BUTTON_BACK = "Revenir à l'interface actuelle";

    private const string SIDEBAR = 'nav[aria-label="Navigation principale"]';

    public function testToggleRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_POST, self::URL_TOGGLE);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testToggleRejectsAnInvalidCsrfToken(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(Request::METHOD_POST, self::URL_TOGGLE, ['_token' => 'invalid']);

        $this->assertResponseStatusCodeSame(403);
        $this->assertFalse($this->currentUser($client)->hasNewInterface());
    }

    public function testToggleSwitchesToTheRedesignedShellAndBack(): void
    {
        $client = self::createLoggedInAsUserClient();

        $crawler = $client->request(Request::METHOD_GET, self::URL_MY_ACCOUNT);
        $this->assertSelectorNotExists(self::SIDEBAR);
        $client->submit($crawler->selectButton(self::BUTTON_TRY)->form());

        $this->assertResponseRedirects(self::URL_MY_ACCOUNT);
        $crawler = $client->followRedirect();
        $this->assertTrue($this->currentUser($client)->hasNewInterface());
        $this->assertSelectorExists(self::SIDEBAR);
        $this->assertSelectorExists('nav[aria-label="Onglets"] a[href="/other"]');
        $this->assertSelectorTextContains('.alert-success', 'nouvelle interface');

        $client->submit($crawler->selectButton(self::BUTTON_BACK)->form());
        $client->followRedirect();
        $this->assertFalse($this->currentUser($client)->hasNewInterface());
        $this->assertSelectorNotExists(self::SIDEBAR);
    }

    public function testTheSidebarMarksTheCurrentPage(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $client->request(Request::METHOD_GET, '/events');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextSame(self::SIDEBAR.' a[aria-current="page"]', 'Calendrier');
    }

    public function testMembersDoNotSeeTheManagementArea(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $client->request(Request::METHOD_GET, '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('#am-nav-management');
    }

    /**
     * Every page extends base.html.twig, so the redesigned shell must render around today's page bodies.
     *
     * @return iterable<string, array{string, string}>
     */
    public static function pagesInTheRedesignedShell(): iterable
    {
        foreach (['/', '/events', '/my-club', '/licensees', '/my-profile', '/other', self::URL_MY_ACCOUNT] as $url) {
            yield 'member '.$url => ['user1@ladg.com', $url];
        }

        foreach (['/events/manage', '/club/members', '/club-application/manage', '/club-equipment'] as $url) {
            yield 'club admin '.$url => ['clubadmin@ladg.com', $url];
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pagesInTheRedesignedShell')]
    public function testPagesRenderInTheRedesignedShell(string $email, string $url): void
    {
        $client = self::createClient();
        $user = self::getContainer()->get(EntityManagerInterface::class)->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertInstanceOf(User::class, $user);
        $client->loginUser($user);
        $this->enableNewInterface($client);

        $client->request(Request::METHOD_GET, $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(self::SIDEBAR);
    }

    private function enableNewInterface(KernelBrowser $client): void
    {
        $user = $this->currentUser($client);
        $user->setNewInterface(true);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
    }

    private function currentUser(KernelBrowser $client): User
    {
        /** @var User $sessionUser */
        $sessionUser = $client->getContainer()->get('security.token_storage')->getToken()?->getUser()
            ?? throw new \LogicException('Not logged in');

        $user = self::getContainer()->get(EntityManagerInterface::class)->find(User::class, $sessionUser->getId());
        $this->assertInstanceOf(User::class, $user);
        self::getContainer()->get(EntityManagerInterface::class)->refresh($user);

        return $user;
    }
}
