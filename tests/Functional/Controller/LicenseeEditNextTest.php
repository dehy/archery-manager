<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Licensee;
use App\Entity\Season;
use App\Entity\User;
use App\Tests\application\LoggedInTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

/**
 * Licensee edit page in the redesigned interface (licensee/edit_next.html.twig). Access is covered by
 * LicenseeControllerTest, which runs on today's interface.
 */
final class LicenseeEditNextTest extends LoggedInTestCase
{
    public function testTodaysInterfaceKeepsItsEditPage(): void
    {
        $client = self::createLoggedInAsUserClient();
        $licensee = $this->ownLicensee($client);

        $client->request(Request::METHOD_GET, \sprintf('/licensee/%d/edit', $licensee->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertAnySelectorTextContains('h1', 'Modifier le licencié');
        $this->assertSelectorNotExists('.am-crumb');
    }

    public function testAMemberSeesTheFormOfTheirArcher(): void
    {
        $client = self::createLoggedInAsUserClient();
        $licensee = $this->ownLicensee($client, newInterface: true);

        $client->request(Request::METHOD_GET, \sprintf('/licensee/%d/edit', $licensee->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modifier le profil');
        // Back to the profile, with the archer's full name: it is the member's own archer
        $this->assertSelectorExists(\sprintf('a.am-crumb[href="/licensee/%d"]', $licensee->getId()));
        $this->assertSelectorTextContains('a.am-crumb', $licensee->getFullname());
        $this->assertInputValueSame('licensee_form[firstname]', (string) $licensee->getFirstname());
        // The FFTA code's help text is tied to the field
        $this->assertSelectorExists('#licensee_form_fftaMemberCode[aria-describedby="licensee_form_fftaMemberCode_help"]');
        $this->assertSelectorExists('nav[aria-label="Navigation principale"] a[aria-current="page"][href="/licensees"]');
    }

    public function testAClubAdminEditsAnotherMemberOfTheClub(): void
    {
        $client = self::createLoggedInAsClubAdminClient();
        $admin = $this->ownLicensee($client, newInterface: true);
        $season = Season::seasonForDate(new \DateTimeImmutable());
        $club = $admin->getLicenseForSeason($season)?->getClub();
        $member = null;
        foreach (self::getContainer()->get(EntityManagerInterface::class)->getRepository(Licensee::class)->findAll() as $licensee) {
            if ($licensee !== $admin && null === $member && $licensee->getLicenseForSeason($season)?->getClub() === $club) {
                $member = $licensee;
            }
        }

        $this->assertInstanceOf(Licensee::class, $member);

        $client->request(Request::METHOD_GET, \sprintf('/licensee/%d/edit', $member->getId()));

        $this->assertResponseIsSuccessful();
        // The member's data, not the admin's own archer
        $this->assertInputValueSame('licensee_form[firstname]', (string) $member->getFirstname());
        $this->assertSelectorTextContains('a.am-crumb', $member->getFullname());
        $this->assertSelectorExists(\sprintf('a.am-crumb[href="/licensee/%d"]', $member->getId()));
    }

    public function testSavingGoesBackToTheProfile(): void
    {
        $client = self::createLoggedInAsUserClient();
        $licensee = $this->ownLicensee($client, newInterface: true);

        $crawler = $client->request(Request::METHOD_GET, \sprintf('/licensee/%d/edit', $licensee->getId()));
        $client->submit($crawler->selectButton('Enregistrer')->form(['licensee_form[firstname]' => 'Renommé']));

        $this->assertResponseRedirects(\sprintf('/licensee/%d', $licensee->getId()));
        $client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Renommé');
    }

    public function testAnInvalidFormStaysOnThePageWithItsErrors(): void
    {
        $client = self::createLoggedInAsUserClient();
        $licensee = $this->ownLicensee($client, newInterface: true);

        $crawler = $client->request(Request::METHOD_GET, \sprintf('/licensee/%d/edit', $licensee->getId()));
        $client->submit($crawler->selectButton('Enregistrer')->form(['licensee_form[birthdate]' => 'pas une date']));

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorExists('#licensee_form_birthdate.is-invalid');
    }

    private function ownLicensee(KernelBrowser $client, bool $newInterface = false): Licensee
    {
        /** @var User $sessionUser */
        $sessionUser = $client->getContainer()->get('security.token_storage')->getToken()?->getUser()
            ?? throw new \LogicException('Not logged in');
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->find(User::class, $sessionUser->getId());
        $this->assertInstanceOf(User::class, $user);

        $user->setNewInterface($newInterface);
        $entityManager->flush();

        $licensee = $user->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        return $licensee;
    }
}
