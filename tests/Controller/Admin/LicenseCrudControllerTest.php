<?php

namespace App\Tests\Controller\Admin;

use App\DBAL\Types\GenderType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseType;
use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\User;
use App\Tests\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;

class LicenseCrudControllerTest extends LoggedInTestCase
{
    public function testWelcomeEmailIsSentAfterPersisting(): void
    {
        $client = static::createLoggedInAsAdminClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user->setGender(GenderType::MALE)
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setEmail('john.doe@acme.org')
            ->setPassword('password');

        $licensee = (new Licensee())
            ->setUser($user)
            ->setGender(GenderType::MALE)
            ->setFirstname($user->getFirstname())
            ->setLastname($user->getLastname())
            ->setBirthdate(new \DateTime('1994-01-01T00:00:00Z'));

        $entityManager->persist($user);
        $entityManager->persist($licensee);
        $entityManager->flush();

        $crawler = $client->request('GET', '/admin?crudAction=new&crudControllerFqcn=App%5CController%5CAdmin%5CLicenseCrudController');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer')->form();
        $form['License[licensee]'] = (string) $licensee->getId();
        $form['License[season]'] = (string) Season::seasonForDate(new \DateTimeImmutable());
        $form['License[type]'] = LicenseType::ADULTES_CLUB;
        $form['License[activities]'] = LicenseActivityType::CL;

        $client->submit($form);

        self::assertResponseRedirects();
        self::assertQueuedEmailCount(1);

        $email = $this->getMailerMessage();

        self::assertEmailHtmlBodyContains($email, 'John');
    }
}
