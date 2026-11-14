<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Enum\InterfaceMode;
use App\Enum\InterfaceTheme;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mon compte → Apparence: theme and mode of the redesigned interface (UserController::appearance / saveAppearance)
 * and the attributes of <html> that select the look (base_next.html.twig).
 */
final class AppearanceTest extends LoggedInTestCase
{
    public function testANewAccountGetsTheSoftThemeFollowingTheDevice(): void
    {
        $user = new User();

        $this->assertSame(InterfaceTheme::Soft, $user->getInterfaceTheme());
        $this->assertSame(InterfaceMode::System, $user->getInterfaceMode());
    }

    public function testAMemberChoosesAThemeAndAMode(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $crawler = $client->request(Request::METHOD_GET, '/my-account/appearance');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Apparence');
        $this->assertSelectorExists('input[name="theme"][value="soft"][checked]');
        $this->assertSelectorExists('input[name="mode"][value="system"][checked]');
        $this->assertSelectorTextContains('.am-theme-card', 'Doux');
        // Each preview is drawn in its own theme
        $this->assertSelectorExists('.am-theme-preview[data-am-theme="colorful"]');

        $form = $crawler->selectButton('Enregistrer')->form(['theme' => 'colorful', 'mode' => 'dark']);
        $client->submit($form);

        $this->assertResponseRedirects('/my-account/appearance');
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'thème Coloré, mode sombre');
        $this->assertSelectorExists('html[data-am-theme="colorful"][data-am-mode="dark"]');
        $this->assertSelectorExists('input[name="theme"][value="colorful"][checked]');

        $user = $this->currentUser($client);
        $this->assertSame(InterfaceTheme::Colorful, $user->getInterfaceTheme());
        $this->assertSame(InterfaceMode::Dark, $user->getInterfaceMode());
    }

    #[DataProvider('looks')]
    public function testThePageCarriesTheStoredLook(InterfaceTheme $theme, InterfaceMode $mode): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client, $theme, $mode);

        $client->request(Request::METHOD_GET, '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(\sprintf('html[data-am-theme="%s"][data-am-mode="%s"]', $theme->value, $mode->value));
        // The browser colour follows the ground of the look; "system" leaves the choice to the browser
        $this->assertSelectorCount(InterfaceMode::System === $mode ? 2 : 1, 'meta[name="theme-color"]');
    }

    /**
     * @return iterable<string, array{InterfaceTheme, InterfaceMode}>
     */
    public static function looks(): iterable
    {
        foreach (InterfaceTheme::cases() as $theme) {
            foreach (InterfaceMode::cases() as $mode) {
                yield $theme->value.' '.$mode->value => [$theme, $mode];
            }
        }
    }

    #[DataProvider('invalidChoices')]
    public function testAnUnknownChoiceIsRefused(string $theme, string $mode): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);
        $crawler = $client->request(Request::METHOD_GET, '/my-account/appearance');
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request(Request::METHOD_POST, '/my-account/appearance', ['_token' => $token, 'theme' => $theme, 'mode' => $mode]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(InterfaceTheme::Soft, $this->currentUser($client)->getInterfaceTheme());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidChoices(): iterable
    {
        yield 'unknown theme' => ['high-contrast', 'light'];
        yield 'unknown mode' => ['colorful', 'sepia'];
        yield 'nothing' => ['', ''];
    }

    public function testTheChoiceNeedsAValidCsrfToken(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $client->request(Request::METHOD_POST, '/my-account/appearance', ['_token' => 'invalid', 'theme' => 'colorful', 'mode' => 'dark']);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(InterfaceTheme::Soft, $this->currentUser($client)->getInterfaceTheme());
    }

    public function testTodaysInterfaceIsUnaffected(): void
    {
        $client = self::createLoggedInAsUserClient();

        $client->request(Request::METHOD_GET, '/my-account/appearance');
        $this->assertResponseRedirects('/my-account');

        $client->request(Request::METHOD_GET, '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('html[data-am-theme]');
        $this->assertSelectorNotExists('a[href="/my-account/appearance"]');
    }

    private function enableNewInterface(KernelBrowser $client, InterfaceTheme $theme = InterfaceTheme::Soft, InterfaceMode $mode = InterfaceMode::System): void
    {
        $this->currentUser($client)->setNewInterface(true)->setInterfaceTheme($theme)->setInterfaceMode($mode);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
    }

    private function currentUser(KernelBrowser $client): User
    {
        /** @var User $sessionUser */
        $sessionUser = $client->getContainer()->get('security.token_storage')->getToken()?->getUser()
            ?? throw new \LogicException('Not logged in');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        $user = $entityManager->find(User::class, $sessionUser->getId());
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }
}
