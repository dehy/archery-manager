<?php

namespace App\Tests\application\Controller\Admin;

use App\DBAL\Types\GenderType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseType;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\User;
use App\Factory\ClubFactory;
use App\Factory\LicenseeFactory;
use App\Factory\UserFactory;
use App\Tests\application\LoggedInTestCase;
use App\Tests\SecurityTrait;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class LicenseCrudControllerTest extends LoggedInTestCase
{
    use Factories;
    use ResetDatabase;

    public function testWelcomeEmailIsSentAfterPersisting(): void
    {
        // 1. Arrange
        $client = $this->createClient();
        $licensee = LicenseeFactory::createOne();
        $club = ClubFactory::createOne();
        $userFirstname = $licensee->getUser()->getFirstname();
        $client->loginUser(UserFactory::new()->admin()->create()->object());

        // 2. Act
        $crawler = $client->request('GET', '/admin?crudAction=new&crudControllerFqcn=App%5CController%5CAdmin%5CLicenseCrudController');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Créer')->form();
        $form['License[licensee]'] = (string) $licensee->getId();
        $form['License[club]'] = (string) $club->getId();
        $form['License[season]'] = (string) Season::seasonForDate(new \DateTimeImmutable());
        $form['License[type]'] = LicenseType::ADULTES_CLUB;
        $form['License[activities]'] = LicenseActivityType::CL;

        $client->submit($form);
        $email = $this->getMailerMessage();

        // 3. Assert
        self::assertResponseRedirects('http://localhost/admin?crudAction=new&crudControllerFqcn=App%5CController%5CAdmin%5CLicenseCrudController');
        self::assertQueuedEmailCount(1);
        self::assertEmailHtmlBodyContains($email, $userFirstname);
    }
}
