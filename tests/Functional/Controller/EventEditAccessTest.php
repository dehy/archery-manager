<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Club;
use App\Entity\ContestEvent;
use App\Entity\Result;
use App\Entity\Season;
use App\Entity\User;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

/**
 * Entering contest results and replacing an event's files (mandate, results) is for the club admins of the event's
 * club and the admins (EventVoter::EDIT), whatever the buttons show.
 */
final class EventEditAccessTest extends LoggedInTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function editPages(): iterable
    {
        yield 'results' => ['/events/%s/results/edit'];
        yield 'mandate' => ['/events/%s/attachments/mandate/edit'];
        yield 'results file' => ['/events/%s/attachments/results/edit'];
    }

    #[DataProvider('editPages')]
    public function testAMemberCannotOpenIt(string $page): void
    {
        $client = self::createLoggedInAsUserClient();
        $contest = $this->contestOfTheUsersClub($client);

        $client->request(Request::METHOD_GET, \sprintf($page, $contest->getSlug()));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAMemberCannotPostResults(): void
    {
        $client = self::createLoggedInAsUserClient();
        $contest = $this->contestOfTheUsersClub($client);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $before = $entityManager->getRepository(Result::class)->count(['event' => $contest]);

        $client->request(Request::METHOD_POST, \sprintf('/events/%s/results/edit', $contest->getSlug()), ['event_results' => []]);

        $this->assertResponseStatusCodeSame(403);
        $entityManager->clear();
        $this->assertSame($before, $entityManager->getRepository(Result::class)->count(['event' => $contest->getId()]));
    }

    #[DataProvider('editPages')]
    public function testAClubAdminOfTheEventsClubCanOpenIt(string $page): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $contest = $this->contestOfTheUsersClub($client);

        $client->request(Request::METHOD_GET, \sprintf($page, $contest->getSlug()));

        $this->assertResponseIsSuccessful();
    }

    #[DataProvider('editPages')]
    public function testAClubAdminOfAnotherClubCannotOpenIt(string $page): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $contest = $this->contestOfAnotherClub($client);

        $client->request(Request::METHOD_GET, \sprintf($page, $contest->getSlug()));

        $this->assertResponseStatusCodeSame(403);
    }

    private function contestOfTheUsersClub(KernelBrowser $client): ContestEvent
    {
        $club = $this->club($client);
        foreach ($this->contests() as $contest) {
            if ($contest->getClub() === $club) {
                return $contest;
            }
        }

        $this->fail("No contest of the user's club in the fixtures");
    }

    private function contestOfAnotherClub(KernelBrowser $client): ContestEvent
    {
        $club = $this->club($client);
        foreach ($this->contests() as $contest) {
            if ($contest->getClub() instanceof Club && $contest->getClub() !== $club) {
                return $contest;
            }
        }

        // The fixtures hold another club: give it a contest for this test
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $other = array_first(array_filter(
            $entityManager->getRepository(Club::class)->findAll(),
            static fn (Club $candidate): bool => $candidate !== $club,
        ));
        $this->assertInstanceOf(Club::class, $other);
        $contest = $this->contests()[0];
        $contest->setClub($other);

        $entityManager->flush();

        return $contest;
    }

    /**
     * @return list<ContestEvent>
     */
    private function contests(): array
    {
        return self::getContainer()->get(EntityManagerInterface::class)->getRepository(ContestEvent::class)->findAll();
    }

    private function club(KernelBrowser $client): Club
    {
        /** @var User $sessionUser */
        $sessionUser = $client->getContainer()->get('security.token_storage')->getToken()?->getUser()
            ?? throw new \LogicException('Not logged in');
        $user = self::getContainer()->get(EntityManagerInterface::class)->find(User::class, $sessionUser->getId());
        $this->assertInstanceOf(User::class, $user);

        $club = $user->getLicensees()->first()?->getLicenseForSeason(Season::seasonForDate(new \DateTimeImmutable()))?->getClub();
        $this->assertInstanceOf(Club::class, $club);

        return $club;
    }
}
