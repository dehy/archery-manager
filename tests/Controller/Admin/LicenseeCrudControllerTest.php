<?php

namespace App\Tests\Controller\Admin;

use App\DBAL\Types\GenderType;
use App\Entity\User;
use App\Tests\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;

class LicenseeCrudControllerTest extends LoggedInTestCase
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

        $entityManager->persist($user);
        $entityManager->flush();

        $userId = $user->getId();

        $crawler = $client->request('GET', '/admin?crudAction=new&crudControllerFqcn=App%5CController%5CAdmin%5CLicenseeCrudController');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Créer')->form();
        $form['Licensee[user]'] = $userId;
        $form['Licensee[gender]'] = GenderType::MALE;
        $form['Licensee[firstname]'] = 'John';
        $form['Licensee[lastname]'] = 'Doe';
        $form['Licensee[birthdate]'] = '1994-01-01';

        $client->submit($form);

        self::assertResponseRedirects();
        self::assertQueuedEmailCount(1);

        $email = $this->getMailerMessage();

        self::assertEmailHtmlBodyContains($email, 'John');
    }
}
