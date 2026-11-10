<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\PictureFileTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uploading and deleting profile pictures from the profile page.
 */
final class LicenseePictureControllerTest extends WebTestCase
{
    use PictureFileTrait;

    private const string MEMBER = 'user1@ladg.com';

    private const string OTHER_MEMBER = 'user2@ladg.com';

    private const string COACH = 'coach@ladg.com';

    private const string CLUB_ADMIN = 'clubadmin@ladg.com';

    private const string OTHER_CLUB_MEMBER = 'adult1@ladb.com';

    private const string FORM_FIELD = 'profile_picture[picture]';

    public function testAMemberUploadsItsOwnPictureFromTheProfilePage(): void
    {
        $client = $this->clientFor(self::MEMBER);
        $licensee = $this->licenseeOf(self::MEMBER);

        $this->upload($client, $licensee, self::pngPath());

        $this->assertResponseRedirects('/licensee/'.$licensee->getId());
        $picture = $this->refreshed($licensee)->getProfilePicture();
        $this->assertInstanceOf(LicenseeAttachment::class, $picture);
        $this->assertTrue($picture->isManualUpload());
        $this->assertSame('image/png', $picture->getFile()->getMimeType());

        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'Photo de profil mise à jour.');
        $this->assertSelectorTextContains('#profilePictureModal', 'Photo importée manuellement');
    }

    public function testUploadingAgainReplacesThePicture(): void
    {
        $client = $this->clientFor(self::MEMBER);
        $licensee = $this->licenseeOf(self::MEMBER);

        $this->upload($client, $licensee, self::pngPath());
        $first = $this->refreshed($licensee)->getProfilePicture()?->getId();
        $this->upload($client, $licensee, self::pngPath(200, 200));

        $pictures = $this->refreshed($licensee)->getAttachments()->filter(static fn (LicenseeAttachment $a): bool => 'profile_picture' === $a->getType());
        $this->assertCount(1, $pictures);
        $this->assertNotSame($first, $pictures->first()->getId());
    }

    public function testAClubAdminEditsThePictureOfAMemberOfTheClub(): void
    {
        $client = $this->clientFor(self::CLUB_ADMIN);
        $licensee = $this->licenseeOf(self::MEMBER);

        $this->upload($client, $licensee, self::pngPath());

        $this->assertResponseRedirects('/licensee/'.$licensee->getId());
        $this->assertInstanceOf(LicenseeAttachment::class, $this->refreshed($licensee)->getProfilePicture());
    }

    public function testACoachHasNoButtonAndIsForbidden(): void
    {
        $client = $this->clientFor(self::COACH);
        $licensee = $this->licenseeOf(self::MEMBER);

        $client->request(Request::METHOD_GET, '/licensee/'.$licensee->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotExists('#profilePictureModal');

        $client->request(Request::METHOD_POST, '/licensee/'.$licensee->getId().'/picture');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $client->request(Request::METHOD_POST, '/licensee/'.$licensee->getId().'/picture/delete');
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAMemberCannotEditSomeoneElsesPicture(): void
    {
        $client = $this->clientFor(self::OTHER_MEMBER);
        $licensee = $this->licenseeOf(self::MEMBER);

        $client->request(Request::METHOD_POST, '/licensee/'.$licensee->getId().'/picture', [], [self::FORM_FIELD => self::uploadedPng()]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertNotInstanceOf(\App\Entity\LicenseeAttachment::class, $this->refreshed($licensee)->getProfilePicture());
    }

    public function testAClubAdminOfAnotherClubIsForbidden(): void
    {
        $client = $this->clientFor(self::CLUB_ADMIN);
        $licensee = $this->licenseeOf(self::OTHER_CLUB_MEMBER);

        $client->request(Request::METHOD_POST, '/licensee/'.$licensee->getId().'/picture');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAFileThatIsNotAPictureIsRejected(): void
    {
        $client = $this->clientFor(self::MEMBER);
        $licensee = $this->licenseeOf(self::MEMBER);

        $path = tempnam(sys_get_temp_dir(), 'not_a_picture_');
        file_put_contents($path, 'definitely not an image');
        $this->upload($client, $licensee, $path);

        $this->assertResponseRedirects('/licensee/'.$licensee->getId());
        $this->assertNotInstanceOf(\App\Entity\LicenseeAttachment::class, $this->refreshed($licensee)->getProfilePicture());
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert-danger', 'La photo doit être une image JPEG, PNG ou WebP.');
    }

    public function testATooSmallPictureIsRejected(): void
    {
        $client = $this->clientFor(self::MEMBER);
        $licensee = $this->licenseeOf(self::MEMBER);

        $this->upload($client, $licensee, self::pngPath(50, 50));

        $this->assertNotInstanceOf(\App\Entity\LicenseeAttachment::class, $this->refreshed($licensee)->getProfilePicture());
    }

    public function testTheDeleteButtonRemovesThePicture(): void
    {
        $client = $this->clientFor(self::MEMBER);
        $licensee = $this->licenseeOf(self::MEMBER);
        $this->upload($client, $licensee, self::pngPath());

        $crawler = $client->request(Request::METHOD_GET, '/licensee/'.$licensee->getId());
        $client->submit($crawler->selectButton('Supprimer la photo')->form());

        $this->assertResponseRedirects('/licensee/'.$licensee->getId());
        $this->assertNotInstanceOf(\App\Entity\LicenseeAttachment::class, $this->refreshed($licensee)->getProfilePicture());
    }

    public function testDeletingWithoutAValidTokenKeepsThePicture(): void
    {
        $client = $this->clientFor(self::MEMBER);
        $licensee = $this->licenseeOf(self::MEMBER);
        $this->upload($client, $licensee, self::pngPath());

        $client->request(Request::METHOD_POST, '/licensee/'.$licensee->getId().'/picture/delete', ['_token' => 'forged']);

        $this->assertResponseRedirects('/licensee/'.$licensee->getId());
        $this->assertInstanceOf(LicenseeAttachment::class, $this->refreshed($licensee)->getProfilePicture());
    }

    private function upload(KernelBrowser $client, Licensee $licensee, string $path): void
    {
        $crawler = $client->request(Request::METHOD_GET, '/licensee/'.$licensee->getId());
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('#profilePictureModal form')->first()->form();
        $field = $form[self::FORM_FIELD];
        $this->assertInstanceOf(FileFormField::class, $field);
        $field->upload($path);
        $client->submit($form);
    }

    private function clientFor(string $email): KernelBrowser
    {
        $client = self::createClient();
        $client->loginUser($this->user($email));

        return $client;
    }

    private function user(string $email): User
    {
        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail($email);
        $this->assertInstanceOf(User::class, $user, \sprintf('Fixture user "%s" not found.', $email));

        return $user;
    }

    private function licenseeOf(string $email): Licensee
    {
        $licensee = $this->user($email)->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee);

        return $licensee;
    }

    private function refreshed(Licensee $licensee): Licensee
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $entityManager->clear();

        $fresh = $entityManager->find(Licensee::class, $licensee->getId());
        $this->assertInstanceOf(Licensee::class, $fresh);

        return $fresh;
    }
}
