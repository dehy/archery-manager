<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\Licensee;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Api\ApiTokenManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class of the API endpoint tests: bearer tokens are issued straight from the token manager
 * (the login itself is covered by AuthControllerTest).
 */
abstract class ApiWebTestCase extends WebTestCase
{
    protected const string MEMBER = 'user1@ladg.com';

    protected const string OTHER_MEMBER = 'user2@ladg.com';

    protected const string COACH = 'coach@ladg.com';

    protected const string CLUB_ADMIN = 'clubadmin@ladg.com';

    protected const string ADMIN = 'admin@acme.org';

    protected const string OTHER_CLUB_MEMBER = 'adult1@ladb.com';

    protected const string APPLICANT = 'applicant1@ladg.com';

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
     * @param array<string, string> $headers extra server parameters, e.g. ['HTTP_X_SEASON' => '2025']
     */
    protected function get(KernelBrowser $client, string $url, string $token, array $headers = []): void
    {
        $client->request(Request::METHOD_GET, $url, server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token, ...$headers]);
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
}
