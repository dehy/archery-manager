<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\ContestEvent;
use App\Entity\Result;
use App\Entity\User;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contest results of the redesigned interface (event/results_next.html.twig).
 */
final class ResultsNextTest extends LoggedInTestCase
{
    /** "Firstname L.": what a plain member may see of other members */
    private const string SHORT_NAME = '/^\S.*\s\p{Lu}\.$/u';

    public function testTodaysInterfaceKeepsItsResultsPage(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(Request::METHOD_GET, \sprintf('/events/%s/results', $this->contestWithResults()->getSlug()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.am-stats');
    }

    public function testAMemberSeesTheRankingWithShortNamesAndNoLinks(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);
        $contest = $this->contestWithResults();

        $crawler = $client->request(Request::METHOD_GET, \sprintf('/events/%s/results', $contest->getSlug()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Résultats');
        $this->assertSelectorTextContains('.am-stats', 'Archers du club');
        $names = $crawler->filter('.am-results tbody td:nth-child(2)')->each(static fn (Crawler $cell): string => trim($cell->text()));
        $this->assertNotEmpty($names);
        foreach ($names as $name) {
            $this->assertMatchesRegularExpression(self::SHORT_NAME, $name);
        }

        $this->assertSelectorNotExists('.am-results a[href^="/licensee/"]');
        $this->assertSelectorNotExists('a[href$="/results/edit"]');
    }

    public function testEachTableIsRankedByPlace(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $crawler = $client->request(Request::METHOD_GET, \sprintf('/events/%s/results', $this->contestWithResults()->getSlug()));

        $crawler->filter('.am-results')->each(function (Crawler $table): void {
            $places = array_filter(
                $table->filter('tbody .am-results-place')->each(static fn (Crawler $cell): string => trim($cell->text())),
                static fn (string $place): bool => '—' !== $place,
            );
            $sorted = $places;
            sort($sorted, \SORT_NUMERIC);
            $this->assertSame($sorted, array_values($places));
        });
    }

    public function testAClubAdminSeesFullNamesLinksAndTheEntryButton(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $this->enableNewInterface($client);
        $contest = $this->contestWithResults();

        $client->request(Request::METHOD_GET, \sprintf('/events/%s/results', $contest->getSlug()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.am-results a[href^="/licensee/"]');
        $this->assertSelectorExists(\sprintf('a[href="/events/%s/results/edit"]', $contest->getSlug()));
    }

    private function contestWithResults(): ContestEvent
    {
        $result = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Result::class)->findOneBy([]);
        $this->assertInstanceOf(Result::class, $result);

        $contest = $result->getEvent();
        $this->assertInstanceOf(ContestEvent::class, $contest);

        return $contest;
    }

    private function enableNewInterface(KernelBrowser $client): void
    {
        /** @var User $sessionUser */
        $sessionUser = $client->getContainer()->get('security.token_storage')->getToken()?->getUser()
            ?? throw new \LogicException('Not logged in');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->find(User::class, $sessionUser->getId());
        $this->assertInstanceOf(User::class, $user);

        $user->setNewInterface(true);
        $entityManager->flush();
    }
}
