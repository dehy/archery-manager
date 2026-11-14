<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\DBAL\Types\EventParticipationStateType;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Entity\Group;
use App\Entity\Licensee;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * The dashboard of the redesigned interface (homepage/index_next.html.twig) and its one-tap answer to trainings
 * (app_event_presence).
 */
final class DashboardNextTest extends LoggedInTestCase
{
    private const string BUTTON_ABSENT = 'Je serai absent·e';

    private const string BUTTON_BACK = 'Je viens finalement';

    public function testTodaysInterfaceKeepsItsDashboard(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(Request::METHOD_GET, '/');

        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h5', 'Prochains événements');
        $this->assertSelectorNotExists('.am-hero');
    }

    public function testTheDashboardShowsTheNextEventTheFollowingOnesAndTheLicence(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $client->request(Request::METHOD_GET, '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Bonjour');
        $this->assertSelectorTextContains('.am-hero .am-hero-eyebrow', 'Prochain rendez-vous');
        $this->assertSelectorExists('.am-hero h2 a[href^="/events/"]');
        $this->assertSelectorTextContains('#am-upcoming-title', 'À venir');
        $this->assertSelectorExists('.am-agenda .am-agenda-item .am-status');
        $this->assertSelectorTextContains('#am-license-title', 'Ma licence');
    }

    public function testAMemberSaysTheyWillMissATrainingThenComesBack(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $crawler = $client->request(Request::METHOD_GET, '/');
        $form = $this->presenceForm($crawler, self::BUTTON_ABSENT);
        $event = $this->eventOf($form);

        $client->submit($form->form());
        $this->assertResponseRedirects('/');
        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'absent·e');
        $this->assertSame(EventParticipationStateType::NOT_GOING, $this->participation($client, $event)?->getParticipationState());

        $client->submit($this->presenceFormFor($crawler, $event, self::BUTTON_BACK)->form());
        $this->assertResponseRedirects('/');
        $client->followRedirect();
        $this->assertSame(EventParticipationStateType::REGISTERED, $this->participation($client, $event)?->getParticipationState());
    }

    public function testThePresenceAnswerRejectsAnInvalidCsrfToken(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $crawler = $client->request(Request::METHOD_GET, '/');
        $event = $this->eventOf($this->presenceForm($crawler, self::BUTTON_ABSENT));

        $client->request(Request::METHOD_POST, \sprintf('/events/%s/presence', $event->getSlug()), ['_token' => 'invalid', 'present' => '0']);

        $this->assertResponseStatusCodeSame(403);
        $this->assertNotSame(EventParticipationStateType::NOT_GOING, $this->participation($client, $event)?->getParticipationState());
    }

    public function testContestsAreAnsweredOnTheirPageOnly(): void
    {
        $client = self::createLoggedInAsUserClient();
        $contest = self::getContainer()->get(EntityManagerInterface::class)->getRepository(ContestEvent::class)->findOneBy([]);
        $this->assertInstanceOf(ContestEvent::class, $contest);

        $client->request(Request::METHOD_POST, \sprintf('/events/%s/presence', $contest->getSlug()), ['present' => '0']);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testATrainingReservedToOtherGroupsIsNotAnswered(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);
        $crawler = $client->request(Request::METHOD_GET, '/');
        $form = $this->presenceForm($crawler, self::BUTTON_ABSENT);
        $event = $this->eventOf($form);

        // The club moves the training to a group the member is not in between the page and the answer
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $licensee = $this->licensee($client);
        $otherGroup = array_first(array_filter(
            $entityManager->getRepository(Group::class)->findAll(),
            static fn (Group $group): bool => !$licensee->getGroups()->contains($group),
        ));
        $this->assertInstanceOf(Group::class, $otherGroup);
        foreach ($event->getAssignedGroups()->toArray() as $group) {
            $event->removeAssignedGroup($group);
        }

        $event->addAssignedGroup($otherGroup);
        $entityManager->flush();

        $client->submit($form->form());

        $this->assertResponseRedirects(\sprintf('/events/%s', $event->getSlug()));
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', "n'a pas été enregistrée");
        $this->assertNotInstanceOf(EventParticipation::class, $this->participation($client, $event));
    }

    private function presenceForm(Crawler $crawler, string $button): Crawler
    {
        $forms = $crawler->filter('form[action$="/presence"]')->reduce(
            static fn (Crawler $form): bool => str_starts_with(trim($form->filter('button')->text()), $button),
        );
        $this->assertGreaterThan(0, $forms->count(), \sprintf('No "%s" button on the dashboard', $button));

        return $forms->first();
    }

    private function presenceFormFor(Crawler $crawler, Event $event, string $button): Crawler
    {
        $form = $crawler->filter(\sprintf('form[action="/events/%s/presence"]', $event->getSlug()));
        $this->assertCount(1, $form);
        $this->assertSame($button, trim($form->filter('button')->text()));

        return $form;
    }

    private function eventOf(Crawler $form): Event
    {
        $this->assertSame(1, preg_match('#^/events/(.+)/presence$#', (string) $form->attr('action'), $matches));
        /** @var EventRepository $repository */
        $repository = self::getContainer()->get(EntityManagerInterface::class)->getRepository(Event::class);
        $event = $repository->findBySlug($matches[1]);
        $this->assertInstanceOf(Event::class, $event);

        return $event;
    }

    private function participation(KernelBrowser $client, Event $event): ?EventParticipation
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        return $entityManager->getRepository(EventParticipation::class)->findOneBy([
            'event' => $event->getId(),
            'participant' => $this->licensee($client)->getId(),
        ]);
    }

    private function licensee(KernelBrowser $client): Licensee
    {
        $licensee = $this->currentUser($client)->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        return $licensee;
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
