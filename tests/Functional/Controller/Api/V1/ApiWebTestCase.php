<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\Licensee;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Api\ApiTokenManager;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

/**
 * Base class of the API endpoint tests: bearer tokens are issued straight from the token manager
 * (the login itself is covered by AuthControllerTest).
 */
abstract class ApiWebTestCase extends WebTestCase
{
    /**
     * The season the fixtures hold their licenses for (see fixtures/licensee_ladg.yml). Requests pin it
     * with X-Season, so the tests do not depend on today's date; bump it with the fixtures.
     */
    protected const int FIXTURE_SEASON = 2027;

    protected const string MEMBER = 'user1@ladg.com';

    protected const string OTHER_MEMBER = 'user2@ladg.com';

    protected const string COACH = 'coach@ladg.com';

    protected const string CLUB_ADMIN = 'clubadmin@ladg.com';

    protected const string ADMIN = 'admin@acme.org';

    protected const string OTHER_CLUB_MEMBER = 'adult1@ladb.com';

    protected const string APPLICANT = 'applicant1@ladg.com';

    private const string SPEC_URI = 'https://archery-manager.test/openapi';

    private static ?Validator $specValidator = null;

    protected function user(string $email): User
    {
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        $this->assertInstanceOf(User::class, $user, \sprintf('Fixture user %s is missing.', $email));

        return $user;
    }

    protected function licenseeOf(string $email): Licensee
    {
        $licensee = $this->user($email)->getLicensees()->first();
        $this->assertInstanceOf(Licensee::class, $licensee, \sprintf('Fixture user %s has no licensee.', $email));

        return $licensee;
    }

    protected function tokenFor(string $email): string
    {
        return self::getContainer()->get(ApiTokenManager::class)->issue($this->user($email))->accessToken;
    }

    /**
     * @param array<string, string> $headers extra server parameters, e.g. ['HTTP_X_SEASON' => '2025'] (the fixture season is the default)
     */
    protected function get(KernelBrowser $client, string $url, string $token, array $headers = []): void
    {
        $client->request(Request::METHOD_GET, $url, server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'HTTP_X_SEASON' => (string) self::FIXTURE_SEASON,
            ...$headers,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function json(KernelBrowser $client): array
    {
        $decoded = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    /**
     * Asserts that the JSON body of the last response satisfies a schema of docs/api/openapi.yaml, so that
     * the document the mobile client is generated from cannot drift from what the API really sends.
     *
     * @param string $schema    name under components.schemas
     * @param string $subPath   JSON property of the body to validate instead of the whole body, e.g. "licensee"
     */
    protected function assertResponseMatchesSchema(KernelBrowser $client, string $schema, string $subPath = ''): void
    {
        $data = json_decode((string) $client->getResponse()->getContent(), false, flags: \JSON_THROW_ON_ERROR);
        if ('' !== $subPath) {
            $this->assertIsObject($data);
            $this->assertObjectHasProperty($subPath, $data);
            $data = $data->{$subPath};
        }

        $result = $this->specValidator()->validate($data, self::SPEC_URI.'#/components/schemas/'.$schema);

        $error = $result->error();
        $this->assertTrue(
            $result->isValid(),
            \sprintf("The response does not match schema %s:\n%s", $schema, $error instanceof \Opis\JsonSchema\Errors\ValidationError ? json_encode(new ErrorFormatter()->format($error, true), \JSON_PRETTY_PRINT) : ''),
        );
    }

    private function specValidator(): Validator
    {
        if (!self::$specValidator instanceof Validator) {
            $spec = Yaml::parseFile(self::getContainer()->getParameter('kernel.project_dir').'/docs/api/openapi.yaml');
            $validator = new Validator();
            $validator->resolver()->registerRaw(json_decode(json_encode($spec, \JSON_THROW_ON_ERROR), false, flags: \JSON_THROW_ON_ERROR), self::SPEC_URI);
            self::$specValidator = $validator;
        }

        return self::$specValidator;
    }
}
