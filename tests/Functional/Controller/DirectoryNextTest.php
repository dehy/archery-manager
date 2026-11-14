<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\DBAL\Types\LicenseActivityType;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\User;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * The club directory of the redesigned interface (licensee/index_next.html.twig): privacy of names, search on what
 * the viewer may see, filters.
 */
final class DirectoryNextTest extends LoggedInTestCase
{
    /** "Firstname L.": what a plain member may see of other members */
    private const string SHORT_NAME = '/^\S.*\s\p{Lu}\.$/u';

    public function testTodaysInterfaceKeepsItsDirectory(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(Request::METHOD_GET, '/licensees');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.am-directory');
    }

    public function testMembersSeeShortNamesAndNoProfileLinks(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $crawler = $client->request(Request::METHOD_GET, '/licensees');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Annuaire');
        $names = $this->names($crawler);
        $this->assertNotEmpty($names);
        foreach ($names as $name) {
            $this->assertMatchesRegularExpression(self::SHORT_NAME, $name);
        }

        $this->assertSelectorNotExists('.am-directory a[href^="/licensee/"]');
    }

    public function testAMemberFindsSomeoneByFirstNameButNotByTheirHiddenLastName(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);
        $other = $this->otherMember($client);

        $crawler = $client->request(Request::METHOD_GET, '/licensees', ['q' => mb_strtoupper((string) $other->getFirstname())]);
        $this->assertContains($other->getFirstnameWithInitial(), $this->names($crawler));

        $lastname = (string) $other->getLastname();
        $crawler = $client->request(Request::METHOD_GET, '/licensees', ['q' => mb_substr($lastname, 1)]);
        $this->assertNotContains($other->getFirstnameWithInitial(), $this->names($crawler));
    }

    public function testACoachFindsSomeoneByLastNameAndOpensTheirProfile(): void
    {
        $client = self::createLoggedInAsCoachClient();
        $this->enableNewInterface($client);
        $other = $this->otherMember($client);

        $crawler = $client->request(Request::METHOD_GET, '/licensees', ['q' => $other->getLastname()]);

        $this->assertContains($other->getFullname(), $this->names($crawler));
        $this->assertSelectorExists(\sprintf('.am-directory a[href="/licensee/%d"]', $other->getId()));
    }

    public function testTheActivityFilterKeepsTheArchersOfThatActivity(): void
    {
        $client = self::createLoggedInAsCoachClient();
        $this->enableNewInterface($client);
        $other = $this->otherMember($client);
        $season = Season::seasonForDate(new \DateTimeImmutable());
        $activity = $other->getLicenseForSeason($season)?->getActivities()[0] ?? null;
        $this->assertNotNull($activity);

        $crawler = $client->request(Request::METHOD_GET, '/licensees', ['activity' => $activity]);

        $this->assertContains($other->getFullname(), $this->names($crawler));
        $this->assertSelectorTextContains('[role="status"]', 'résultat');
        $label = LicenseActivityType::getReadableValue($activity);
        $crawler->filter('.am-person')->each(function (Crawler $card) use ($label): void {
            $this->assertStringContainsString($label, $card->text());
        });
    }

    public function testAnUnknownActivityIsIgnored(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $crawler = $client->request(Request::METHOD_GET, '/licensees', ['activity' => 'not-an-activity']);

        $this->assertResponseIsSuccessful();
        $this->assertNotEmpty($this->names($crawler));
    }

    /**
     * @return list<string>
     */
    private function names(Crawler $crawler): array
    {
        return $crawler->filter('.am-person-name')->each(static fn (Crawler $name): string => trim($name->text()));
    }

    private function otherMember(KernelBrowser $client): Licensee
    {
        $user = $this->currentUser($client);
        $season = Season::seasonForDate(new \DateTimeImmutable());
        $club = $user->getLicensees()->first()?->getLicenseForSeason($season)?->getClub();
        $this->assertInstanceOf(\App\Entity\Club::class, $club);

        foreach (self::getContainer()->get(EntityManagerInterface::class)->getRepository(Licensee::class)->findAll() as $licensee) {
            $license = $licensee->getLicenseForSeason($season);
            if (!$user->getLicensees()->contains($licensee) && $license?->getClub() === $club
                && [] !== $license->getActivities() && mb_strlen((string) $licensee->getLastname()) > 3) {
                return $licensee;
            }
        }

        $this->fail('No other member in the club');
    }

    private function enableNewInterface(KernelBrowser $client): void
    {
        $this->currentUser($client)->setNewInterface(true);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
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
