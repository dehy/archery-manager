<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Licensee;
use App\Entity\User;
use App\Helper\LicenseeHelper;
use App\Repository\UserRepository;
use App\Tests\application\LoggedInTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Switching the licensee an account acts as, from the menu (?_switch_licensee=<licensee id>).
 */
final class SwitchLicenseeTest extends LoggedInTestCase
{
    public function testTheMenuLinkSwitchesToAnotherLicenseeOfTheAccount(): void
    {
        $client = self::createLoggedInAsUserClient();
        [$first, $second] = $this->licenseesOfUser1();

        $crawler = $client->request(Request::METHOD_GET, '/');
        $this->assertResponseIsSuccessful();
        $this->assertSame($first->getId(), $client->getRequest()->getSession()->get(LicenseeHelper::SESSION_KEY));

        // The link the menu really renders for the other licensee.
        $link = $crawler->filter(\sprintf('a[href*="_switch_licensee=%d"]', $second->getId()))->first();
        $this->assertCount(1, $link, 'The menu offers to switch to the other licensee.');
        $client->request(Request::METHOD_GET, (string) $link->attr('href'));

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertStringNotContainsString('_switch_licensee', (string) $client->getResponse()->headers->get('Location'));
        $this->assertSame($second->getId(), $client->getRequest()->getSession()->get(LicenseeHelper::SESSION_KEY));

        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSame($second->getId(), $client->getRequest()->getSession()->get(LicenseeHelper::SESSION_KEY), 'The selection sticks.');
    }

    public function testALicenseeOfAnotherAccountCannotBeSelected(): void
    {
        $client = self::createLoggedInAsUserClient();
        [$first] = $this->licenseesOfUser1();
        $foreign = $this->user('user2@ladg.com')->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $foreign);

        $client->request(Request::METHOD_GET, '/?_switch_licensee='.$foreign->getId());
        $client->followRedirect();

        $this->assertResponseIsSuccessful();
        $this->assertSame($first->getId(), $client->getRequest()->getSession()->get(LicenseeHelper::SESSION_KEY));
    }

    /**
     * @return array{0: Licensee, 1: Licensee}
     */
    private function licenseesOfUser1(): array
    {
        $licensees = array_values($this->user('user1@ladg.com')->getLicensees()->toArray());
        $this->assertGreaterThanOrEqual(2, \count($licensees), 'The fixture account user1@ladg.com has two licensees.');

        return [$licensees[0], $licensees[1]];
    }

    private function user(string $email): User
    {
        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail($email);
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }
}
