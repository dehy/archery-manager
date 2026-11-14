<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Group;
use App\Entity\User;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

/**
 * The club page of the redesigned interface (club/show_next.html.twig).
 */
final class ClubNextTest extends LoggedInTestCase
{
    public function testTodaysInterfaceKeepsItsClubPage(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(Request::METHOD_GET, '/my-club');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('.am-club-head');
    }

    public function testAMemberSeesTheClubItsTrainingsAndGroups(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);

        $client->request(Request::METHOD_GET, '/my-club');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Archers');
        $this->assertSelectorTextContains('#am-club-trainings-title', 'Entraînements de la semaine');
        // The fixtures hold recurring trainings around now
        $this->assertSelectorExists('.am-agenda a[href^="/events/"]');
        $this->assertSelectorTextContains('#am-club-groups-title', 'Groupes');
        $this->assertSelectorExists('.am-group a[href^="/licensees?group="]');
        // Members don't manage the club
        $this->assertSelectorNotExists('a[href="/my-club/settings"]');
        $this->assertSelectorNotExists('.am-group a[href*="/manage"]');
    }

    public function testAClubAdminGetsTheClubTools(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $this->enableNewInterface($client);

        $client->request(Request::METHOD_GET, '/my-club');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href="/my-club/settings"]');
        $this->assertSelectorExists('.am-group a[href$="/manage"]');
    }

    public function testAGroupDescriptionIsShownAsPlainText(): void
    {
        $client = self::createLoggedInAsUserClient();
        $this->enableNewInterface($client);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $group = $entityManager->getRepository(Group::class)->findOneBy([]);
        $this->assertInstanceOf(Group::class, $group);
        $group->setDescription('<script>alert(1)</script><b>Le mardi</b>');
        $entityManager->flush();

        $client->request(Request::METHOD_GET, '/my-club');

        $this->assertSelectorNotExists('.am-group script');
        $this->assertSelectorNotExists('.am-group b');
        $this->assertAnySelectorTextContains('.am-group', 'Le mardi');
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
