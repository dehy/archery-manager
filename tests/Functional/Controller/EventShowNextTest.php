<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\DBAL\Types\EventParticipationStateType;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\EventParticipation;
use App\Entity\Licensee;
use App\Entity\TrainingEvent;
use App\Entity\User;
use App\Helper\EventHelper;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * The event page of the redesigned interface (event/show_next.html.twig) and its inline answer form.
 */
final class EventShowNextTest extends LoggedInTestCase
{
    private const string SAVE = 'Enregistrer';

    public function testTodaysInterfaceKeepsItsEventPage(): void
    {
        $client = self::createLoggedInAsUserClient();
        $event = $this->upcoming($client, TrainingEvent::class, true);

        $client->request(Request::METHOD_GET, '/events/'.$event->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#participationModal');
        $this->assertSelectorNotExists('.am-answer');
    }

    public function testAMemberRegistersToAContestWithATargetFaceAndAStart(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);
        $contest = $this->upcoming($client, ContestEvent::class, true);

        $crawler = $client->request(Request::METHOD_GET, '/events/'.$contest->getSlug());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', $contest->getTitle());
        $this->assertSelectorTextContains('#am-answer-title', 'Ma réponse');
        $this->assertSelectorTextContains('.am-options', 'Je suis inscrit·e');
        $this->assertSelectorTextContains('.am-options', "Intéressé·e, je vais m'inscrire");
        $this->assertSelectorNotExists('#participationModal');

        $form = $crawler->selectButton(self::SAVE)->form();
        $prefix = $form->getFormNode()->getAttribute('name');
        $this->choice($form, $prefix.'[participationState]')->select(EventParticipationStateType::REGISTERED);
        $targetTypes = $this->choice($form, $prefix.'[targetType]')->availableOptionValues();
        $this->choice($form, $prefix.'[targetType]')->select(array_last($targetTypes));
        $this->assertContains('5', $this->choice($form, $prefix.'[departure]')->availableOptionValues());
        $this->choice($form, $prefix.'[departure]')->select('1');
        $client->submit($form);

        $this->assertResponseRedirects('/events/'.$contest->getSlug());
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Votre réponse est enregistrée');
        $this->assertSelectorTextContains('.am-page-head .am-status-ok', 'Inscrit·e');

        $participation = $this->participation($client, $contest);
        $this->assertSame(EventParticipationStateType::REGISTERED, $participation?->getParticipationState());
        $this->assertSame(array_last($targetTypes), $participation->getTargetType());
        $this->assertSame(1, $participation->getDeparture());
    }

    public function testAMemberSaysTheyWillMissATraining(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);
        $training = $this->upcoming($client, TrainingEvent::class, true);

        $crawler = $client->request(Request::METHOD_GET, '/events/'.$training->getSlug());
        $this->assertSelectorTextContains('#am-participants-title', 'Qui vient ?');
        $this->assertSelectorNotExists('select[name$="[targetType]"]');
        // A training only asks "present or not"
        $this->assertSelectorNotExists('select[name$="[activity]"]');

        $form = $crawler->selectButton(self::SAVE)->form();
        $prefix = $form->getFormNode()->getAttribute('name');
        $this->choice($form, $prefix.'[participationState]')->select(EventParticipationStateType::NOT_GOING);
        $client->submit($form);

        $this->assertResponseRedirects('/events/'.$training->getSlug());
        $client->followRedirect();
        $this->assertSelectorTextContains('.am-page-head .am-status-no', 'Absent·e');
        $this->assertSame(EventParticipationStateType::NOT_GOING, $this->participation($client, $training)?->getParticipationState());
    }

    public function testAnEventForOtherGroupsSaysWhyItCantBeAnswered(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);
        $training = $this->upcoming($client, TrainingEvent::class, false);

        $client->request(Request::METHOD_GET, '/events/'.$training->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.am-answer');
        $this->assertSelectorTextContains('#am-answer-unavailable', 'Réservé au');
        $this->assertSelectorTextContains('.am-page-head', "Réservé à d'autres groupes");
    }

    /**
     * @param class-string<Event> $class
     */
    private function upcoming(KernelBrowser $client, string $class, bool $answerable): Event
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $eventHelper = self::getContainer()->get(EventHelper::class);
        $licensee = $this->licensee($client);

        $events = $entityManager->createQueryBuilder()
            ->select('e')->from($class, 'e')
            ->where('e.startsAt > :now')->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.startsAt')
            ->getQuery()->getResult();
        $event = array_first(array_filter(
            $events,
            static fn (Event $event): bool => $eventHelper->canLicenseeParticipateInEvent($licensee, $event) === $answerable,
        ));
        $this->assertInstanceOf(Event::class, $event, \sprintf('No upcoming %s the member %s answer', $class, $answerable ? 'can' : "can't"));

        return $event;
    }

    private function choice(Form $form, string $name): ChoiceFormField
    {
        $field = $form[$name];
        $this->assertInstanceOf(ChoiceFormField::class, $field);

        return $field;
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
