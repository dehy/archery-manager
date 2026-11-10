<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use App\Tests\PictureFileTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST / DELETE /api/v1/licensees/{id}/picture.
 */
final class LicenseePictureControllerTest extends ApiWebTestCase
{
    use PictureFileTrait;

    public function testAMemberUploadsItsOwnPictureAndCanFetchIt(): void
    {
        $client = self::createClient();
        // The in-memory storage would be emptied by a kernel reboot between the upload and the download.
        $client->disableReboot();

        $licensee = $this->licenseeOf(self::MEMBER);
        $token = $this->tokenFor(self::MEMBER);

        $this->upload($client, $licensee, $token, self::uploadedPng());

        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'LicenseeProfile', 'licensee');
        $profile = $this->json($client)['licensee'];
        $this->assertTrue($profile['can_edit_picture']);
        $this->assertNotNull($profile['picture_url']);
        $this->assertTrue($this->refreshed($licensee)->getProfilePicture()?->isManualUpload());

        $this->get($client, '/api/v1/licensees/'.$licensee->getId().'/picture', $token);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'image/png');
    }

    public function testAClubAdminUploadsThePictureOfAMemberOfTheClub(): void
    {
        $client = self::createClient();
        $licensee = $this->licenseeOf(self::MEMBER);

        $this->upload($client, $licensee, $this->tokenFor(self::CLUB_ADMIN), self::uploadedPng());

        $this->assertResponseIsSuccessful();
        $this->assertInstanceOf(LicenseeAttachment::class, $this->refreshed($licensee)->getProfilePicture());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function forbiddenEditors(): iterable
    {
        yield 'another member of the club' => [self::OTHER_MEMBER];
        yield 'a coach of the club' => [self::COACH];
        yield 'a member of another club' => [self::OTHER_CLUB_MEMBER];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('forbiddenEditors')]
    public function testOthersCannotEditThePicture(string $editor): void
    {
        $client = self::createClient();
        $licensee = $this->licenseeOf(self::MEMBER);
        $token = $this->tokenFor($editor);

        $this->upload($client, $licensee, $token, self::uploadedPng());
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertResponseMatchesSchema($client, 'Error');

        $this->delete($client, $licensee, $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertNotInstanceOf(\App\Entity\LicenseeAttachment::class, $this->refreshed($licensee)->getProfilePicture());
    }

    public function testTheProfileTellsACoachItCannotEditThePicture(): void
    {
        $client = self::createClient();

        $this->get($client, '/api/v1/licensees/'.$this->licenseeOf(self::MEMBER)->getId(), $this->tokenFor(self::COACH));

        $this->assertResponseIsSuccessful();
        $this->assertFalse($this->json($client)['licensee']['can_edit_picture']);
    }

    public function testAMissingFileIsABadRequest(): void
    {
        $client = self::createClient();
        $licensee = $this->licenseeOf(self::MEMBER);

        $this->upload($client, $licensee, $this->tokenFor(self::MEMBER), null);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseMatchesSchema($client, 'Error');
        $this->assertSame('bad_request', $this->json($client)['error']);
    }

    public function testAFileThatIsNotAPictureFailsValidation(): void
    {
        $client = self::createClient();
        $licensee = $this->licenseeOf(self::MEMBER);

        $this->upload($client, $licensee, $this->tokenFor(self::MEMBER), self::uploadedText());

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertResponseMatchesSchema($client, 'Error');
        $error = $this->json($client);
        $this->assertSame('validation_failed', $error['error']);
        $this->assertSame('The picture must be a JPEG, PNG or WebP image.', $error['message']);
        $this->assertNotInstanceOf(\App\Entity\LicenseeAttachment::class, $this->refreshed($licensee)->getProfilePicture());
    }

    public function testATooSmallPictureFailsValidation(): void
    {
        $client = self::createClient();
        $licensee = $this->licenseeOf(self::MEMBER);

        $this->upload($client, $licensee, $this->tokenFor(self::MEMBER), self::uploadedPng(50, 50));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSame('validation_failed', $this->json($client)['error']);
    }

    public function testDeleteRemovesThePictureAndCanBeRepeated(): void
    {
        $client = self::createClient();
        $licensee = $this->licenseeOf(self::MEMBER);
        $token = $this->tokenFor(self::MEMBER);
        $this->upload($client, $licensee, $token, self::uploadedPng());

        $this->delete($client, $licensee, $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertNotInstanceOf(\App\Entity\LicenseeAttachment::class, $this->refreshed($licensee)->getProfilePicture());

        $this->delete($client, $licensee, $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    private function upload(KernelBrowser $client, Licensee $licensee, string $token, ?UploadedFile $file): void
    {
        $client->request(
            Request::METHOD_POST,
            '/api/v1/licensees/'.$licensee->getId().'/picture',
            files: $file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile ? ['picture' => $file] : [],
            server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'HTTP_X_SEASON' => (string) self::FIXTURE_SEASON],
        );
    }

    private function delete(KernelBrowser $client, Licensee $licensee, string $token): void
    {
        $client->request(
            Request::METHOD_DELETE,
            '/api/v1/licensees/'.$licensee->getId().'/picture',
            server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token],
        );
    }

    private function refreshed(Licensee $licensee): Licensee
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();

        $fresh = $entityManager->find(Licensee::class, $licensee->getId());
        $this->assertInstanceOf(Licensee::class, $fresh);

        return $fresh;
    }
}
