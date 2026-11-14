<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

/**
 * The calendar of the redesigned interface (event/index_next.html.twig) and the one-tap answer that comes back to it.
 */
final class CalendarNextTest extends LoggedInTestCase
{
    public function testTodaysInterfaceKeepsItsCalendar(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(Request::METHOD_GET, '/events');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.calendar');
        $this->assertSelectorNotExists('.am-calendar');
    }

    public function testTheMonthShowsEachEventWithItsKindAndMyAnswer(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $client->request(Request::METHOD_GET, '/events');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', ucfirst(new \IntlDateFormatter('fr', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, null, null, 'MMMM')->format(new \DateTimeImmutable())));
        $this->assertSelectorTextContains('.am-page-head', 'Saison');
        $this->assertSelectorExists('.am-calendar-grid .am-calendar-day.is-today');
        $this->assertSelectorExists('.am-calendar-event.am-type-training');
        $this->assertSelectorExists('.am-calendar-event .visually-hidden');
        // The list is there too, for phones
        $this->assertSelectorExists('.am-agenda .am-agenda-item .am-status');
    }

    public function testTheListViewHasNoGrid(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $client->request(Request::METHOD_GET, '/events?view=list');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.am-calendar-grid');
        $this->assertSelectorTextContains('nav[aria-label="Affichage"] [aria-current="page"]', 'Liste');
        $this->assertSelectorExists('.am-agenda-item');
    }

    public function testAOneTapAnswerComesBackToTheCalendar(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);
        $now = new \DateTimeImmutable();
        $here = \sprintf('/events?m=%d&y=%d&view=list', $now->format('n'), $now->format('Y'));

        $crawler = $client->request(Request::METHOD_GET, $here);
        $form = $crawler->filter('form[action$="/presence"]')->first();
        $this->assertCount(1, $form, 'No one-tap answer in the calendar list this month');
        $this->assertSame($here, $form->filter('input[name="return_to"]')->attr('value'));

        $client->submit($form->form());

        $this->assertResponseRedirects($here);
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', "C'est noté");
    }

    #[DataProvider('foreignReturns')]
    public function testAOneTapAnswerNeverLeavesTheApp(string $returnTo): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $crawler = $client->request(Request::METHOD_GET, '/events?view=list');
        $form = $crawler->filter('form[action$="/presence"]')->first()->form();
        $form['return_to'] = $returnTo;
        $client->submit($form);

        $this->assertResponseRedirects('/');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function foreignReturns(): iterable
    {
        yield 'another host' => ['https://example.com/events'];
        yield 'protocol-relative' => ['//example.com/events'];
        yield 'backslash' => ['/\example.com'];
        yield 'another page' => ['/admin'];
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
