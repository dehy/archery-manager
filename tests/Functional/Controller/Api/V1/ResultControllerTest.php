<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\Club;
use App\Entity\ContestEvent;
use App\Entity\HobbyContestEvent;
use App\Entity\Licensee;
use App\Entity\Result;
use App\Entity\TrainingEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResultControllerTest extends ApiWebTestCase
{
    private const string HISTORY_URL = '/api/v1/results';

    private const string LADG = 'Les Archers de Guyenne';

    public function testItRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, self::HISTORY_URL);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $client->request(Request::METHOD_GET, '/api/v1/events/1/results');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testTheHistoryListsOnlyMyResultsNewestContestFirst(): void
    {
        $client = self::createClient();
        $me = $this->licenseeOf(self::MEMBER);
        $old = $this->contest('Ancien', '2027-05-01 09:00');
        $recent = $this->contest('Récent', '2027-06-12 09:00');
        $this->storeResult($old, $me, 'S1', 'CL', 540);
        $mine = $this->storeResult($recent, $me, 'S1', 'CL', 560);
        $this->storeResult($recent, $this->licenseeOf(self::OTHER_MEMBER), 'S1', 'CL', 580);

        $this->get($client, self::HISTORY_URL, $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'ResultHistory');
        $results = $this->json($client)['results'];
        $this->assertSame([560, 540], array_column($results, 'total'));
        $this->assertSame($mine->getId(), $results[0]['id']);
        $this->assertSame(2027, $results[0]['event']['season']);
        $this->assertSame(600, $results[0]['max_total']);
        $this->assertSame(['9', '10'], $results[0]['tie_break_labels']);
        $this->assertSame('monospot', $results[0]['target_type']['code']);
    }

    public function testCompoundTieBreaksAreTenAndX(): void
    {
        $client = self::createClient();
        $this->storeResult($this->contest('Concours', '2027-06-12 09:00'), $this->licenseeOf(self::MEMBER), 'S1', 'CO', 590);

        $this->get($client, self::HISTORY_URL, $this->tokenFor(self::MEMBER));

        $this->assertSame(['10', 'X'], $this->json($client)['results'][0]['tie_break_labels']);
    }

    public function testAHobbyContestHasItsOwnMaximum(): void
    {
        $client = self::createClient();
        $event = new HobbyContestEvent();
        $this->fill($event, 'Loisir', '2027-06-12 09:00', $this->club(self::LADG));
        $this->storeResult($event, $this->licenseeOf(self::MEMBER), 'S1', 'CL', 250);

        $this->get($client, self::HISTORY_URL, $this->tokenFor(self::MEMBER));

        $this->assertSame(300, $this->json($client)['results'][0]['max_total']);
    }

    public function testTheHistoryNeedsALicenseForTheSelectedSeason(): void
    {
        $client = self::createClient();

        $this->get($client, self::HISTORY_URL, $this->tokenFor(self::MEMBER), ['HTTP_X_SEASON' => '2040']);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testTheResultsOfAContestAreSortedAndNamedLikeTheDirectory(): void
    {
        $client = self::createClient();
        $contest = $this->contest('Concours', '2027-06-12 09:00');
        $me = $this->licenseeOf(self::MEMBER);
        $other = $this->licenseeOf(self::OTHER_MEMBER);
        $this->storeResult($contest, $other, 'S1', 'CL', 500);
        $this->storeResult($contest, $me, 'S1', 'CL', 560);
        $this->storeResult($contest, $me, 'U11', 'CL', 400);

        $this->get($client, '/api/v1/events/'.$contest->getId().'/results', $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'ContestResults');
        $results = $this->json($client)['results'];
        $this->assertSame(['U11', 'S1', 'S1'], array_column(array_column($results, 'age_category'), 'code'), 'Youngest category first.');
        foreach ($results as $result) {
            $this->assertMatchesRegularExpression('/^\S+ \p{Lu}\.$/u', $result['licensee']['display_name'], 'A member sees "Firstname L.".');
            $this->assertSame($result['licensee']['id'] === $me->getId(), $result['is_mine']);
        }

        $names = array_column(array_column($results, 'licensee'), 'display_name');
        $sameCategory = \array_slice($names, 1);
        $sorted = $sameCategory;
        sort($sorted);
        $this->assertSame($sorted, $sameCategory, 'Archers of a category are sorted by the name shown.');
    }

    public function testACoachSeesFullNames(): void
    {
        $client = self::createClient();
        $contest = $this->contest('Concours', '2027-06-12 09:00');
        $archer = $this->licenseeOf(self::MEMBER);
        $this->storeResult($contest, $archer, 'S1', 'CL', 560);

        $this->get($client, '/api/v1/events/'.$contest->getId().'/results', $this->tokenFor(self::COACH));

        $this->assertResponseIsSuccessful();
        $this->assertSame($archer->getFullname(), $this->json($client)['results'][0]['licensee']['display_name']);
    }

    public function testAContestWithoutResultsIsAnEmptyList(): void
    {
        $client = self::createClient();
        $contest = $this->contest('Concours', '2027-06-12 09:00');

        $this->get($client, '/api/v1/events/'.$contest->getId().'/results', $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $this->json($client)['results']);
    }

    public function testATrainingHasNoResults(): void
    {
        $client = self::createClient();
        $training = new TrainingEvent();
        $this->fill($training, 'Entraînement', '2027-06-12 18:00', $this->club(self::LADG));

        $this->get($client, '/api/v1/events/'.$training->getId().'/results', $this->tokenFor(self::MEMBER));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseMatchesSchema($client, 'Error');
    }

    public function testTheResultsOfAContestOfAnotherClubAreForbidden(): void
    {
        $client = self::createClient();
        $contest = new ContestEvent();
        $contest->setContestType('individual');
        $this->fill($contest, 'Chez les autres', '2027-06-12 09:00', $this->club('Les Archers du Bosquet'));

        $this->get($client, '/api/v1/events/'.$contest->getId().'/results', $this->tokenFor(self::MEMBER));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    private function club(string $name): Club
    {
        $club = $this->entityManager()->getRepository(Club::class)->findOneBy(['name' => $name]);
        $this->assertInstanceOf(Club::class, $club);

        return $club;
    }

    private function contest(string $name, string $startsAt): ContestEvent
    {
        $event = new ContestEvent();
        $event->setContestType('individual');
        $this->fill($event, $name, $startsAt, $this->club(self::LADG));

        return $event;
    }

    private function fill(ContestEvent|TrainingEvent $event, string $name, string $startsAt, Club $club): void
    {
        $start = new \DateTimeImmutable($startsAt);
        $event->setName($name)
            ->setDiscipline('indoor')
            ->setStartsAt($start)
            ->setEndsAt($start->modify('+8 hours'))
            ->setAddress('Gymnase')
            ->setAllDay(false)
            ->setClub($club);
        $event->setSlug(strtolower($name).'-'.uniqid());
        $this->entityManager()->persist($event);
        $this->entityManager()->flush();
    }

    private function storeResult(ContestEvent $event, Licensee $licensee, string $ageCategory, string $activity, int $total): Result
    {
        $result = new Result();
        $result->setEvent($event)
            ->setLicensee($licensee)
            ->setDiscipline('indoor')
            ->setAgeCategory($ageCategory)
            ->setActivity($activity)
            ->setDistance(18)
            ->setTargetType('monospot')
            ->setTargetSize(40)
            ->setScore1(intdiv($total, 2))
            ->setScore2($total - intdiv($total, 2))
            ->setTotal($total);
        $this->entityManager()->persist($result);
        $this->entityManager()->flush();

        return $result;
    }
}
