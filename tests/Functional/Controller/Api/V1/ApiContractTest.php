<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\DBAL\Types\LicenseeAttachmentType;
use App\Entity\LicenseeAttachment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;

/**
 * The real responses of every endpoint, validated against the schemas of docs/api/openapi.yaml.
 * If a field is renamed, retyped, or becomes nullable, this fails before the generated mobile client does.
 */
final class ApiContractTest extends ApiWebTestCase
{
    // ── Meta and authentication ────────────────────────────────────────

    public function testHealth(): void
    {
        $client = self::createClient();

        $client->request(Request::METHOD_GET, '/api/v1/health');

        $this->assertResponseMatchesSchema($client, 'Health');
    }

    public function testLoginAndRefreshReturnATokenPair(): void
    {
        $client = self::createClient();

        $client->jsonRequest(Request::METHOD_POST, '/api/v1/auth/login', ['email' => self::MEMBER, 'password' => 'user', 'device_name' => 'Contract test']);
        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'TokenPair');
        $refreshToken = $this->json($client)['refresh_token'];

        $client->jsonRequest(Request::METHOD_POST, '/api/v1/auth/refresh', ['refresh_token' => $refreshToken]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'TokenPair');
    }

    public function testMe(): void
    {
        $client = self::createClient();

        $this->get($client, '/api/v1/me', $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $this->assertResponseMatchesSchema($client, 'Me');
    }

    // ── Home, in its three states ──────────────────────────────────────

    public function testHomeDashboard(): void
    {
        $client = self::createClient();

        $this->get($client, '/api/v1/home', $this->tokenFor(self::COACH));

        $this->assertSame('dashboard', $this->json($client)['state']);
        $this->assertResponseMatchesSchema($client, 'Home');
    }

    public function testHomeWithoutLicense(): void
    {
        $client = self::createClient();

        $this->get($client, '/api/v1/home', $this->tokenFor(self::APPLICANT));

        $this->assertSame('no_license', $this->json($client)['state']);
        $this->assertResponseMatchesSchema($client, 'Home');
    }

    public function testHomeOfABlankAccount(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist(new User()
            ->setEmail('blank-contract@example.com')
            ->setFirstname('Blank')
            ->setLastname('Account')
            ->setGender('F')
            ->setBirthdate(new \DateTimeImmutable('1990-01-01'))
            ->setRoles(['ROLE_USER'])
            ->setPassword('!!'));
        $entityManager->flush();

        $this->get($client, '/api/v1/home', $this->tokenFor('blank-contract@example.com'));

        $this->assertSame('blank_account', $this->json($client)['state']);
        $this->assertResponseMatchesSchema($client, 'Home');
    }

    // ── Club and directory ─────────────────────────────────────────────

    public function testClub(): void
    {
        $client = self::createClient();

        $this->get($client, '/api/v1/club', $this->tokenFor(self::MEMBER));

        $this->assertResponseMatchesSchema($client, 'ClubOverview');
    }

    public function testMemberDirectory(): void
    {
        $client = self::createClient();

        $this->get($client, '/api/v1/club/members?per_page=100', $this->tokenFor(self::MEMBER));
        $this->assertResponseMatchesSchema($client, 'MemberPage');

        $this->get($client, '/api/v1/club/members?group=none&q=a&page=2&per_page=3', $this->tokenFor(self::COACH));
        $this->assertResponseMatchesSchema($client, 'MemberPage');
    }

    // ── Licensee ───────────────────────────────────────────────────────

    public function testProfileWithLicenseAndAttachments(): void
    {
        $client = self::createClient();
        $licensee = $this->licenseeOf(self::MEMBER);
        $this->attach($licensee->getId(), LicenseeAttachmentType::MEDICAL_CERTIFICATE);
        $this->attach($licensee->getId(), LicenseeAttachmentType::PROFILE_PICTURE);

        $this->get($client, '/api/v1/licensees/'.$licensee->getId(), $this->tokenFor(self::MEMBER));

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $this->json($client)['licensee']['attachments']);
        $this->assertResponseMatchesSchema($client, 'LicenseeProfile', 'licensee');
    }

    public function testProfileWithoutLicenseForTheSeason(): void
    {
        $client = self::createClient();

        $this->get($client, '/api/v1/licensees/'.$this->licenseeOf(self::MEMBER)->getId(), $this->tokenFor(self::MEMBER), ['HTTP_X_SEASON' => '2019']);

        $this->assertNull($this->json($client)['licensee']['license']);
        $this->assertResponseMatchesSchema($client, 'LicenseeProfile', 'licensee');
    }

    // ── Errors ─────────────────────────────────────────────────────────

    public function testEveryKindOfErrorHasTheErrorShape(): void
    {
        $client = self::createClient();
        $token = $this->tokenFor(self::MEMBER);
        $otherMemberUrl = '/api/v1/licensees/'.$this->licenseeOf(self::OTHER_MEMBER)->getId();

        $client->request(Request::METHOD_GET, '/api/v1/me');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertResponseMatchesSchema($client, 'Error');

        $this->get($client, '/api/v1/me', 'not-a-token');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertResponseMatchesSchema($client, 'Error');

        $this->get($client, $otherMemberUrl, $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->assertResponseMatchesSchema($client, 'Error');

        $this->get($client, '/api/v1/licensees/999999999', $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseMatchesSchema($client, 'Error');

        $this->get($client, '/api/v1/club/members?page=0', $token);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseMatchesSchema($client, 'Error');

        $client->request(Request::METHOD_POST, '/api/v1/auth/login', content: '{}');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        $this->assertResponseMatchesSchema($client, 'Error');

        $client->jsonRequest(Request::METHOD_POST, '/api/v1/auth/login', ['email' => self::MEMBER, 'password' => 'wrong']);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertResponseMatchesSchema($client, 'Error');

        $client->jsonRequest(Request::METHOD_POST, '/api/v1/auth/refresh', ['refresh_token' => 'unknown']);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertResponseMatchesSchema($client, 'Error');
    }

    /**
     * The validator itself must be able to fail, otherwise this whole class proves nothing.
     */
    public function testTheValidatorRejectsAResponseThatDoesNotMatch(): void
    {
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/api/v1/health');

        try {
            $this->assertResponseMatchesSchema($client, 'Error');
        } catch (\PHPUnit\Framework\AssertionFailedError) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('A health response must not satisfy the Error schema.');
    }

    private function attach(?int $licenseeId, string $type): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $licensee = $entityManager->getRepository(\App\Entity\Licensee::class)->find($licenseeId);

        $file = new EmbeddedFile();
        $file->setName('contract/'.$type.'.bin');
        $file->setOriginalName($type.'.bin');
        $file->setMimeType('application/octet-stream');
        $file->setSize(1);

        $attachment = new LicenseeAttachment();
        $attachment->setFile($file);
        $attachment->setType($type);
        $attachment->setSeason(self::FIXTURE_SEASON);
        $attachment->setUpdatedAt(new \DateTimeImmutable());

        $licensee->addAttachment($attachment);
        $entityManager->persist($attachment);
        $entityManager->flush();
    }
}
