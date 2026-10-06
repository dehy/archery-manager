<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\DBAL\Types\LicenseeAttachmentType;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;

final class LicenseeControllerTest extends ApiWebTestCase
{
    private const string PICTURE_BYTES = "\xFF\xD8\xFF\xE0 not really a jpeg";

    private const string CERTIFICATE_BYTES = '%PDF-1.4 medical certificate';

    // ── Profile ────────────────────────────────────────────────────────

    public function testItRequiresAuthentication(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, '/api/v1/licensees/'.$this->licenseeOf(self::MEMBER)->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAMemberSeesItsOwnProfile(): void
    {
        $client = self::createClient();
        $licensee = $this->licenseeOf(self::MEMBER);

        $this->get($client, $this->url($licensee), $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('cache-control', 'no-store, private');
        $profile = $this->json($client)['licensee'];
        $this->assertSame($licensee->getId(), $profile['id']);
        $this->assertSame($licensee->getFirstname(), $profile['firstname']);
        $this->assertSame($licensee->getLastname(), $profile['lastname']);
        $this->assertSame($licensee->getFullname(), $profile['full_name']);
        $this->assertSame($licensee->getFftaMemberCode(), $profile['ffta_member_code']);
        $this->assertSame($licensee->getGender(), $profile['gender']['code']);
        $this->assertNotSame('', $profile['gender']['label']);
        $this->assertIsList($profile['groups']);
        $this->assertSame([], $profile['attachments']);
    }

    public function testTheProfileDescribesTheLicenseOfTheSelectedSeason(): void
    {
        $client = self::createClient();
        $licensee = $this->licenseeOf(self::MEMBER);
        $license = $licensee->getLicenseForSeason(2027);
        $this->assertInstanceOf(\App\Entity\License::class, $license);

        $this->get($client, $this->url($licensee), $this->tokenFor(self::MEMBER));
        $described = $this->json($client)['licensee']['license'];

        $this->assertSame(2027, $described['season']);
        $this->assertSame('Les Archers de Guyenne', $described['club']['name']);
        $this->assertSame($license->getType(), $described['type']['code']);
        $this->assertSame($license->getAgeCategory(), $described['age_category']['code']);
        $this->assertSame($license->getActivities(), array_column($described['activities'], 'code'));
        $this->assertNotSame('', $described['age_category']['label']);

        $this->get($client, $this->url($licensee), $this->tokenFor(self::MEMBER), ['HTTP_X_SEASON' => '2019']);
        $this->assertNull($this->json($client)['licensee']['license']);
    }

    public function testAnUnknownLicenseeIsNotFound(): void
    {
        $client = self::createClient();

        $this->get($client, '/api/v1/licensees/999999999', $this->tokenFor(self::MEMBER));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertSame('not_found', $this->json($client)['error']);
    }

    /**
     * Who may open whose profile: yourself, any admin, and club admins or coaches of the same club only.
     *
     * @return iterable<string, array{string, string, int}>
     */
    public static function profileAccess(): iterable
    {
        yield 'a member, someone else of the same club' => [self::MEMBER, self::OTHER_MEMBER, Response::HTTP_FORBIDDEN];
        yield 'a member, itself' => [self::MEMBER, self::MEMBER, Response::HTTP_OK];
        yield 'a coach, a member of the same club' => [self::COACH, self::MEMBER, Response::HTTP_OK];
        yield 'a club admin, a member of the same club' => [self::CLUB_ADMIN, self::MEMBER, Response::HTTP_OK];
        yield 'an admin, a member of another club' => [self::ADMIN, self::MEMBER, Response::HTTP_OK];
        yield 'a member of another club' => [self::OTHER_CLUB_MEMBER, self::MEMBER, Response::HTTP_FORBIDDEN];
        yield 'an applicant without license' => [self::APPLICANT, self::MEMBER, Response::HTTP_FORBIDDEN];
    }

    #[DataProvider('profileAccess')]
    public function testProfileAccessFollowsTheWebRules(string $viewer, string $target, int $expected): void
    {
        $client = self::createClient();

        $this->get($client, $this->url($this->licenseeOf($target)), $this->tokenFor($viewer));

        $this->assertResponseStatusCodeSame($expected);
    }

    public function testADeniedProfileRevealsNothingAboutThePerson(): void
    {
        $client = self::createClient();
        $target = $this->licenseeOf(self::OTHER_MEMBER);

        $this->get($client, $this->url($target), $this->tokenFor(self::MEMBER));

        $content = (string) $client->getResponse()->getContent();
        $this->assertSame('forbidden', $this->json($client)['error']);
        $this->assertStringNotContainsString((string) $target->getLastname(), $content);
        $this->assertStringNotContainsString((string) $target->getFftaMemberCode(), $content);
    }

    // ── Profile picture ────────────────────────────────────────────────

    public function testALicenseeWithoutPictureHasNoPictureUrlAndTheEndpointIs404(): void
    {
        $client = self::createClient();
        $licensee = $this->licenseeOf(self::MEMBER);

        $this->get($client, $this->url($licensee).'/picture', $this->tokenFor(self::MEMBER));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertStringStartsWith('application/json', (string) $client->getResponse()->headers->get('content-type'));
    }

    public function testThePictureIsStreamedWithItsMimeTypeAndPrivateCaching(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $licensee = $this->licenseeOf(self::OTHER_MEMBER);
        $this->storeAttachment($licensee, LicenseeAttachmentType::PROFILE_PICTURE, 'pictures/'.$licensee->getId().'.jpg', 'image/jpeg', self::PICTURE_BYTES);

        // Any member of the club may see it, as the directory shows it to everyone.
        $this->get($client, $this->url($licensee).'/picture', $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $this->assertSame(self::PICTURE_BYTES, $this->streamed($client));
        $this->assertResponseHeaderSame('content-type', 'image/jpeg');
        $this->assertResponseHeaderSame('content-length', (string) \strlen(self::PICTURE_BYTES));
        $this->assertResponseHeaderSame('x-content-type-options', 'nosniff');
        $this->assertStringContainsString('private', (string) $client->getResponse()->headers->get('cache-control'));
        $this->assertNotNull($client->getResponse()->headers->get('last-modified'));
    }

    public function testThePictureSupportsConditionalRequests(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $licensee = $this->licenseeOf(self::OTHER_MEMBER);
        $this->storeAttachment($licensee, LicenseeAttachmentType::PROFILE_PICTURE, 'pictures/x.jpg', 'image/jpeg', self::PICTURE_BYTES);
        $token = $this->tokenFor(self::MEMBER);

        $this->get($client, $this->url($licensee).'/picture', $token);
        $lastModified = (string) $client->getResponse()->headers->get('last-modified');
        $this->get($client, $this->url($licensee).'/picture', $token, ['HTTP_IF_MODIFIED_SINCE' => $lastModified]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_MODIFIED);
        $this->assertSame('', (string) $client->getResponse()->getContent());
    }

    public function testThePictureOfAnotherClubIsForbidden(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $licensee = $this->licenseeOf(self::OTHER_MEMBER);
        $this->storeAttachment($licensee, LicenseeAttachmentType::PROFILE_PICTURE, 'pictures/y.jpg', 'image/jpeg', self::PICTURE_BYTES);

        $this->get($client, $this->url($licensee).'/picture', $this->tokenFor(self::OTHER_CLUB_MEMBER));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertStringNotContainsString(self::PICTURE_BYTES, (string) $client->getResponse()->getContent());
    }

    public function testThePictureUrlOfTheDirectoryPointsToTheAuthorizedEndpoint(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $licensee = $this->licenseeOf(self::OTHER_MEMBER);
        $this->storeAttachment($licensee, LicenseeAttachmentType::PROFILE_PICTURE, 'pictures/z.jpg', 'image/jpeg', self::PICTURE_BYTES);

        $this->get($client, '/api/v1/club/members?per_page=100', $this->tokenFor(self::MEMBER));

        $members = array_column($this->json($client)['data'], null, 'id');
        $this->assertSame('/api/v1/licensees/'.$licensee->getId().'/picture', $members[$licensee->getId()]['picture_url']);
    }

    // ── Attachments ────────────────────────────────────────────────────

    public function testAnAttachmentIsListedOnTheProfileAndStreamedToItsOwner(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $licensee = $this->licenseeOf(self::MEMBER);
        $attachment = $this->storeAttachment($licensee, LicenseeAttachmentType::MEDICAL_CERTIFICATE, 'certificates/c.pdf', 'application/pdf', self::CERTIFICATE_BYTES, 'certificat médical.pdf');
        $token = $this->tokenFor(self::MEMBER);

        $this->get($client, $this->url($licensee), $token);
        $listed = $this->json($client)['licensee']['attachments'];

        $this->assertCount(1, $listed);
        $this->assertSame($attachment->getId(), $listed[0]['id']);
        $this->assertSame('medical_certificate', $listed[0]['type']['code']);
        $this->assertSame('application/pdf', $listed[0]['mime_type']);
        $this->assertSame('certificat médical.pdf', $listed[0]['file_name']);
        $this->assertSame($this->url($licensee).'/attachments/'.$attachment->getId(), $listed[0]['url']);
        $this->assertStringNotContainsString('certificates/c.pdf', (string) $client->getResponse()->getContent(), 'The storage key is not exposed.');

        $this->get($client, $listed[0]['url'], $token);
        $this->assertResponseIsSuccessful();
        $this->assertSame(self::CERTIFICATE_BYTES, $this->streamed($client));
        $this->assertResponseHeaderSame('content-type', 'application/pdf');
        $this->assertStringStartsWith('inline', (string) $client->getResponse()->headers->get('content-disposition'));
    }

    public function testAnAttachmentCanBeForcedToDownload(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $licensee = $this->licenseeOf(self::MEMBER);
        $attachment = $this->storeAttachment($licensee, LicenseeAttachmentType::MEDICAL_CERTIFICATE, 'certificates/d.pdf', 'application/pdf', self::CERTIFICATE_BYTES, 'certificat médical.pdf');

        $this->get($client, $this->url($licensee).'/attachments/'.$attachment->getId().'?download=1', $this->tokenFor(self::MEMBER));

        $disposition = (string) $client->getResponse()->headers->get('content-disposition');
        $this->assertStringStartsWith('attachment', $disposition);
        $this->assertStringContainsString("filename*=utf-8''certificat%20m%C3%A9dical.pdf", $disposition);
        $this->assertStringContainsString('filename="certificat m_dical.pdf"', $disposition);
    }

    /**
     * Attachments hold medical certificates: only the owner, admins, and club admins or coaches of the club.
     *
     * @return iterable<string, array{string, int}>
     */
    public static function attachmentAccess(): iterable
    {
        yield 'the owner' => [self::MEMBER, Response::HTTP_OK];
        yield 'a coach of the club' => [self::COACH, Response::HTTP_OK];
        yield 'a club admin of the club' => [self::CLUB_ADMIN, Response::HTTP_OK];
        yield 'an admin' => [self::ADMIN, Response::HTTP_OK];
        yield 'another member of the same club' => [self::OTHER_MEMBER, Response::HTTP_FORBIDDEN];
        yield 'a member of another club' => [self::OTHER_CLUB_MEMBER, Response::HTTP_FORBIDDEN];
    }

    #[DataProvider('attachmentAccess')]
    public function testAttachmentAccess(string $viewer, int $expected): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $licensee = $this->licenseeOf(self::MEMBER);
        $attachment = $this->storeAttachment($licensee, LicenseeAttachmentType::MEDICAL_CERTIFICATE, 'certificates/e.pdf', 'application/pdf', self::CERTIFICATE_BYTES);

        $this->get($client, $this->url($licensee).'/attachments/'.$attachment->getId(), $this->tokenFor($viewer));

        $this->assertResponseStatusCodeSame($expected);
        if (Response::HTTP_FORBIDDEN === $expected) {
            $this->assertStringNotContainsString(self::CERTIFICATE_BYTES, (string) $client->getResponse()->getContent());
        }
    }

    public function testAnAttachmentCannotBeReadThroughSomeoneElsesProfile(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $owner = $this->licenseeOf(self::OTHER_MEMBER);
        $attachment = $this->storeAttachment($owner, LicenseeAttachmentType::MEDICAL_CERTIFICATE, 'certificates/f.pdf', 'application/pdf', self::CERTIFICATE_BYTES);

        // The viewer may open its own profile, but the attachment belongs to somebody else.
        $this->get($client, $this->url($this->licenseeOf(self::MEMBER)).'/attachments/'.$attachment->getId(), $this->tokenFor(self::MEMBER));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAnAttachmentWhoseFileIsMissingFromTheStorageIsNotFound(): void
    {
        $client = self::createClient();
        $client->disableReboot();

        $licensee = $this->licenseeOf(self::MEMBER);
        $attachment = $this->storeAttachment($licensee, LicenseeAttachmentType::MISC, 'misc/gone.pdf', 'application/pdf', 'x');
        $this->storage()->delete('misc/gone.pdf');

        $this->get($client, $this->url($licensee).'/attachments/'.$attachment->getId(), $this->tokenFor(self::MEMBER));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function url(Licensee $licensee): string
    {
        return '/api/v1/licensees/'.$licensee->getId();
    }

    private function storage(): FilesystemOperator
    {
        return self::getContainer()->get('licensees_storage');
    }

    /**
     * Stores a file in the (in-memory, in tests) licensees storage and attaches it to the licensee.
     */
    private function storeAttachment(Licensee $licensee, string $type, string $key, string $mimeType, string $content, ?string $originalName = null): LicenseeAttachment
    {
        $this->storage()->write($key, $content);

        $file = new EmbeddedFile();
        $file->setName($key);
        $file->setOriginalName($originalName ?? basename($key));
        $file->setMimeType($mimeType);
        $file->setSize(\strlen($content));

        $attachment = new LicenseeAttachment();
        $attachment->setFile($file);
        $attachment->setType($type);
        $attachment->setSeason(2027);
        $attachment->setUpdatedAt(new \DateTimeImmutable('2026-01-15 10:00:00'));

        $licensee->addAttachment($attachment);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($attachment);
        $entityManager->flush();

        return $attachment;
    }

    /**
     * The body of a streamed response, which getContent() does not return.
     */
    private function streamed(KernelBrowser $client): string
    {
        $response = $client->getInternalResponse();

        return $response->getContent();
    }
}
