<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Tests\application\LoggedInTestCase;

final class UserControllerTest extends LoggedInTestCase
{
    private const string URL_MY_ACCOUNT = '/my-account';

    private const string URL_USER = '/user/';

    // ── My Account ─────────────────────────────────────────────────────

    public function testMyAccountRequiresAuthentication(): void
    {
        $client = self::createClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MY_ACCOUNT);

        $this->assertResponseRedirects();
        $this->assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testMyAccountRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MY_ACCOUNT);

        $this->assertResponseIsSuccessful();
    }

    public function testMyAccountRendersForUser(): void
    {
        $client = self::createLoggedInAsUserClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MY_ACCOUNT);

        $this->assertResponseIsSuccessful();
    }

    // ── Show ───────────────────────────────────────────────────────────

    public function testShowOwnProfileAsAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_USER.$user->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testShowOwnProfileAsUser(): void
    {
        $client = self::createLoggedInAsUserClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_USER.$user->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testShowOtherUserAsAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();

        // Admin can view any user
        /** @var User $admin */
        $admin = $client->getContainer()->get('security.token_storage')->getToken()->getUser();

        // Find a different user via entity manager
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $allUsers = $em->getRepository(User::class)->findAll();
        $otherUser = null;
        foreach ($allUsers as $u) {
            if ($u->getId() !== $admin->getId()) {
                $otherUser = $u;
                break;
            }
        }

        if ($otherUser instanceof User) {
            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_USER.$otherUser->getId());
            $this->assertResponseIsSuccessful();
        } else {
            $this->markTestSkipped('No other user available');
        }
    }

    public function testShowOtherUserDeniedForRegularUser(): void
    {
        $client = self::createLoggedInAsUserClient();

        /** @var User $currentUser */
        $currentUser = $client->getContainer()->get('security.token_storage')->getToken()->getUser();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $allUsers = $em->getRepository(User::class)->findAll();
        $otherUser = null;
        foreach ($allUsers as $u) {
            if ($u->getId() !== $currentUser->getId()) {
                $otherUser = $u;
                break;
            }
        }

        if ($otherUser instanceof User) {
            $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_USER.$otherUser->getId());
            $this->assertResponseStatusCodeSame(403);
        } else {
            $this->markTestSkipped('No other user available');
        }
    }

    // ── Edit ───────────────────────────────────────────────────────────

    public function testEditRendersForAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_USER.$user->getId().'/edit');
        $this->assertResponseIsSuccessful();
    }

    public function testEditDeniedForRegularUser(): void
    {
        $client = self::createLoggedInAsUserClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();

        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_USER.$user->getId().'/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditSubmitUpdatesUser(): void
    {
        $client = self::createLoggedInAsAdminClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_USER.$user->getId().'/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Enregistrer')->form();
        $form['user_form[firstname]'] = 'TestUpdatedFirstname';
        $client->submit($form);

        $this->assertResponseRedirects(self::URL_USER.$user->getId());
    }

    public function testEditNonExistentUserReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_USER.'99999/edit');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowNonExistentUserReturns404(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_USER.'99999');

        $this->assertResponseStatusCodeSame(404);
    }

    // ── Edit Other User as Admin ──────────────────────────────────────

    public function testEditOtherUserAsAdmin(): void
    {
        $client = self::createLoggedInAsAdminClient();

        /** @var User $admin */
        $admin = $client->getContainer()->get('security.token_storage')->getToken()->getUser();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $allUsers = $em->getRepository(User::class)->findAll();
        $otherUser = null;
        foreach ($allUsers as $u) {
            if ($u->getId() !== $admin->getId()) {
                $otherUser = $u;
                break;
            }
        }

        if ($otherUser instanceof User) {
            $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_USER.$otherUser->getId().'/edit');
            $this->assertResponseIsSuccessful();

            $form = $crawler->selectButton('Enregistrer')->form();
            $form['user_form[firstname]'] = 'AdminEdited';
            $client->submit($form);

            $this->assertResponseRedirects(self::URL_USER.$otherUser->getId());
        } else {
            $this->markTestSkipped('No other user available');
        }
    }

    // ── My Account Content ────────────────────────────────────────────

    public function testMyAccountDisplaysUserInfo(): void
    {
        $client = self::createLoggedInAsAdminClient();
        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_MY_ACCOUNT);

        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('body')->count());
    }

    // ── Show own profile as user ──────────────────────────────────────

    public function testShowOwnProfileDisplaysContent(): void
    {
        $client = self::createLoggedInAsUserClient();

        /** @var User $user */
        $user = $client->getContainer()->get('security.token_storage')->getToken()->getUser();

        $crawler = $client->request(\Symfony\Component\HttpFoundation\Request::METHOD_GET, self::URL_USER.$user->getId());
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThan(0, $crawler->filter('body')->count());
    }
}
