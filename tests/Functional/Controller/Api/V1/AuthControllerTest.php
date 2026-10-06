<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use App\Entity\ApiSession;
use App\Entity\SecurityLog;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Api\ApiTokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthControllerTest extends WebTestCase
{
    private const string EMAIL = 'clubadmin@ladg.com';

    private const string PASSWORD = 'user';

    private const string LOGIN_URL = '/api/v1/auth/login';

    private const string REFRESH_URL = '/api/v1/auth/refresh';

    private const string LOGOUT_URL = '/api/v1/auth/logout';

    private const string ME_URL = '/api/v1/me';

    public function testLoginReturnsBearerTokens(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('cache-control', 'no-store, private');
        $this->assertSame('Bearer', $tokens['token_type']);
        $this->assertSame(ApiTokenManager::ACCESS_TOKEN_TTL_SECONDS, $tokens['expires_in']);
        $this->assertNotEmpty($tokens['access_token']);
        $this->assertNotEmpty($tokens['refresh_token']);
        $this->assertNotSame($tokens['access_token'], $tokens['refresh_token']);
    }

    public function testLoginStoresOnlyHashesOfTheTokens(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client, deviceName: 'Pixel 9');

        $session = $this->entityManager()->getRepository(ApiSession::class)
            ->findOneBy(['accessTokenHash' => ApiTokenManager::hash($tokens['access_token'])]);

        $this->assertInstanceOf(ApiSession::class, $session);
        $this->assertSame('Pixel 9', $session->getDeviceName());
        $this->assertNotSame($tokens['access_token'], $session->getAccessTokenHash());
        $this->assertNotSame($tokens['refresh_token'], $session->getRefreshTokenHash());
    }

    public function testLoginWithWrongPasswordIsRejectedAndCounted(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::LOGIN_URL, ['email' => self::EMAIL, 'password' => 'nope']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertSame(1, $this->user()->getFailedLoginAttempts());
        $this->assertSame(1, $this->securityLogCount(SecurityLog::EVENT_FAILED_LOGIN));
    }

    public function testUnknownEmailAndWrongPasswordGiveTheSameResponse(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::LOGIN_URL, ['email' => 'ghost@example.com', 'password' => 'nope']);

        $unknown = (string) $client->getResponse()->getContent();
        $unknownStatus = $client->getResponse()->getStatusCode();

        $client->jsonRequest(Request::METHOD_POST, self::LOGIN_URL, ['email' => self::EMAIL, 'password' => 'nope']);

        $this->assertSame($unknownStatus, $client->getResponse()->getStatusCode());
        $this->assertSame($unknown, (string) $client->getResponse()->getContent());
    }

    public function testSuccessfulLoginResetsFailedAttemptsAndIsLoggedOnce(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::LOGIN_URL, ['email' => self::EMAIL, 'password' => 'nope']);
        $this->login($client);

        $this->assertSame(0, $this->user()->getFailedLoginAttempts());
        $this->assertSame(1, $this->securityLogCount(SecurityLog::EVENT_SUCCESS_LOGIN));
    }

    public function testLockedAccountCannotLogInEvenWithTheRightPassword(): void
    {
        $client = self::createClient();
        $user = $this->user();
        $user->lockAccount(30);
        $this->entityManager()->flush();

        $client->jsonRequest(Request::METHOD_POST, self::LOGIN_URL, ['email' => self::EMAIL, 'password' => self::PASSWORD]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $body = $this->decode($client);
        $this->assertSame('account_locked', $body['error']);
        $this->assertStringContainsString('locked', (string) $body['message']);
    }

    public function testFailedLoginsOnAnAlreadyLockedAccountDoNotExtendTheLock(): void
    {
        $client = self::createClient();
        $user = $this->user();
        $user->setFailedLoginAttempts(10);
        $user->lockAccount(30);
        $this->entityManager()->flush();
        $lockedUntil = $user->getAccountLockedUntil()?->getTimestamp();

        $client->jsonRequest(Request::METHOD_POST, self::LOGIN_URL, ['email' => self::EMAIL, 'password' => 'nope']);
        $client->jsonRequest(Request::METHOD_POST, self::LOGIN_URL, ['email' => self::EMAIL, 'password' => 'nope']);

        $reloaded = $this->user();
        $this->assertSame(10, $reloaded->getFailedLoginAttempts());
        $this->assertSame($lockedUntil, $reloaded->getAccountLockedUntil()?->getTimestamp());
        $this->assertSame(0, $this->securityLogCount(SecurityLog::EVENT_ACCOUNT_LOCKED));
        $this->assertSame(2, $this->securityLogCount(SecurityLog::EVENT_FAILED_LOGIN));
    }

    public function testMeRequiresAToken(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_GET, self::ME_URL);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testMeReturnsTheUserLicenseesAndContext(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client);

        $data = $this->get($client, self::ME_URL, $tokens['access_token']);

        $this->assertResponseIsSuccessful();
        $this->assertSame(self::EMAIL, $data['user']['email']);
        $this->assertNotEmpty($data['licensees']);
        $this->assertSame($data['licensees'][0]['ffta_member_code'], $data['context']['licensee']);
        $this->assertIsInt($data['context']['season']);
    }

    public function testMeDoesNotStartASession(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client);

        $this->get($client, self::ME_URL, $tokens['access_token']);

        $this->assertSame([], $client->getResponse()->headers->getCookies());
    }

    public function testInvalidBearerTokenIsRejectedWithoutTouchingSecurityLogs(): void
    {
        $client = self::createClient();
        $before = $this->securityLogCount(SecurityLog::EVENT_FAILED_LOGIN);

        $this->get($client, self::ME_URL, 'not-a-real-token');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertSame($before, $this->securityLogCount(SecurityLog::EVENT_FAILED_LOGIN));
    }

    public function testAuthenticatedRequestsDoNotWriteLoginLogs(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client);
        $before = $this->securityLogCount(SecurityLog::EVENT_SUCCESS_LOGIN);

        $this->get($client, self::ME_URL, $tokens['access_token']);
        $this->get($client, self::ME_URL, $tokens['access_token']);

        $this->assertSame($before, $this->securityLogCount(SecurityLog::EVENT_SUCCESS_LOGIN));
    }

    public function testExpiredAccessTokenIsRejected(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client);
        $this->expireAccessTokens();

        $this->get($client, self::ME_URL, $tokens['access_token']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testTokenOfALockedAccountIsRejected(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client);
        $this->user()->lockAccount(30);
        $this->entityManager()->flush();

        $this->get($client, self::ME_URL, $tokens['access_token']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRefreshRotatesBothTokens(): void
    {
        $client = self::createClient();
        $first = $this->login($client);

        $second = $this->refresh($client, $first['refresh_token']);

        $this->assertResponseIsSuccessful();
        $this->assertNotSame($first['access_token'], $second['access_token']);
        $this->assertNotSame($first['refresh_token'], $second['refresh_token']);

        $this->get($client, self::ME_URL, $first['access_token']);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $this->get($client, self::ME_URL, $second['access_token']);
        $this->assertResponseIsSuccessful();
    }

    public function testARotatedAwayRefreshTokenIsJustABadToken(): void
    {
        $client = self::createClient();
        $first = $this->login($client);
        $second = $this->refresh($client, $first['refresh_token']);

        $body = $this->refresh($client, $first['refresh_token']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->assertSame('invalid_refresh_token', $body['error']);

        // No replay detection: the session carries on with its current tokens and nothing is flagged.
        $this->assertSame(0, $this->revokedSessionCount());
        $this->assertSame(0, $this->securityLogCount(SecurityLog::EVENT_SUSPICIOUS_ACTIVITY));
        $this->get($client, self::ME_URL, $second['access_token']);
        $this->assertResponseIsSuccessful();
        $this->refresh($client, $second['refresh_token']);
        $this->assertResponseIsSuccessful();
    }

    public function testExpiredRefreshTokenIsRejected(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client);
        $this->entityManager()->createQuery('UPDATE '.ApiSession::class.' s SET s.refreshTokenExpiresAt = :past')
            ->setParameter('past', new \DateTimeImmutable('-1 minute'))
            ->execute();

        $this->refresh($client, $tokens['refresh_token']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRefreshWithoutATokenIsABadRequest(): void
    {
        $client = self::createClient();
        $client->jsonRequest(Request::METHOD_POST, self::REFRESH_URL, []);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLogoutRevokesOnlyThatSession(): void
    {
        $client = self::createClient();
        $phone = $this->login($client, deviceName: 'phone');
        $tablet = $this->login($client, deviceName: 'tablet');

        $this->logout($client, $phone['refresh_token']);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->get($client, self::ME_URL, $phone['access_token']);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $this->refresh($client, $phone['refresh_token']);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $this->get($client, self::ME_URL, $tablet['access_token']);
        $this->assertResponseIsSuccessful();
    }

    public function testLogoutWorksAfterTheAccessTokenExpired(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client);
        $this->expireAccessTokens();

        $client->jsonRequest(Request::METHOD_POST, self::LOGOUT_URL, ['refresh_token' => $tokens['refresh_token']], ['HTTP_AUTHORIZATION' => 'Bearer '.$tokens['access_token']]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->refresh($client, $tokens['refresh_token']);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLogoutWithAnUnknownOrMissingTokenRevealsNothing(): void
    {
        $client = self::createClient();

        $this->logout($client, 'not-a-real-refresh-token');
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->jsonRequest(Request::METHOD_POST, self::LOGOUT_URL);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testLogoutWithARotatedAwayRefreshTokenDoesNothing(): void
    {
        $client = self::createClient();
        $first = $this->login($client);
        $second = $this->refresh($client, $first['refresh_token']);

        $this->logout($client, $first['refresh_token']);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->get($client, self::ME_URL, $second['access_token']);
        $this->assertResponseIsSuccessful();
    }

    public function testRefreshIgnoresAStaleBearerToken(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client);
        $this->expireAccessTokens();

        $client->jsonRequest(Request::METHOD_POST, self::REFRESH_URL, ['refresh_token' => $tokens['refresh_token']], ['HTTP_AUTHORIZATION' => 'Bearer '.$tokens['access_token']]);

        $this->assertResponseIsSuccessful();
    }

    public function testLicenseeHeaderSelectsAnOwnedLicensee(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client);
        $code = $this->get($client, self::ME_URL, $tokens['access_token'])['licensees'][0]['ffta_member_code'];

        $data = $this->get($client, self::ME_URL, $tokens['access_token'], ['HTTP_X_LICENSEE' => $code]);

        $this->assertResponseIsSuccessful();
        $this->assertSame($code, $data['context']['licensee']);
    }

    public function testLicenseeHeaderCannotSelectAnotherUsersLicensee(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client);
        $coach = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'coach@ladg.com']);
        $this->assertInstanceOf(User::class, $coach);
        $foreignCode = $coach->getLicensees()->first()->getFftaMemberCode();

        $this->get($client, self::ME_URL, $tokens['access_token'], ['HTTP_X_LICENSEE' => $foreignCode]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testSeasonHeaderIsHonouredAndValidated(): void
    {
        $client = self::createClient();
        $tokens = $this->login($client);

        $data = $this->get($client, self::ME_URL, $tokens['access_token'], ['HTTP_X_SEASON' => '2025']);
        $this->assertResponseIsSuccessful();
        $this->assertSame(2025, $data['context']['season']);

        $this->get($client, self::ME_URL, $tokens['access_token'], ['HTTP_X_SEASON' => 'abc']);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @return array<string, mixed>
     */
    private function login(KernelBrowser $client, string $email = self::EMAIL, string $password = self::PASSWORD, ?string $deviceName = null): array
    {
        $payload = ['email' => $email, 'password' => $password];
        if (null !== $deviceName) {
            $payload['device_name'] = $deviceName;
        }

        $client->jsonRequest(Request::METHOD_POST, self::LOGIN_URL, $payload);

        return $this->decode($client);
    }

    /**
     * @return array<string, mixed>
     */
    private function refresh(KernelBrowser $client, string $refreshToken): array
    {
        $client->jsonRequest(Request::METHOD_POST, self::REFRESH_URL, ['refresh_token' => $refreshToken]);

        return $this->decode($client);
    }

    private function logout(KernelBrowser $client, string $refreshToken): void
    {
        $client->jsonRequest(Request::METHOD_POST, self::LOGOUT_URL, ['refresh_token' => $refreshToken]);
    }

    private function revokedSessionCount(): int
    {
        return (int) $this->entityManager()
            ->createQuery('SELECT COUNT(s.id) FROM '.ApiSession::class.' s WHERE s.revokedAt IS NOT NULL')
            ->getSingleScalarResult();
    }

    private function expireAccessTokens(): void
    {
        $this->entityManager()->createQuery('UPDATE '.ApiSession::class.' s SET s.accessTokenExpiresAt = :past')
            ->setParameter('past', new \DateTimeImmutable('-1 minute'))
            ->execute();
    }

    /**
     * @param array<string, string> $server
     *
     * @return array<string, mixed>
     */
    private function get(KernelBrowser $client, string $url, string $accessToken, array $server = []): array
    {
        $client->request(Request::METHOD_GET, $url, server: ['HTTP_AUTHORIZATION' => 'Bearer '.$accessToken, ...$server]);

        return $this->decode($client);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(KernelBrowser $client): array
    {
        $content = (string) $client->getResponse()->getContent();
        if ('' === $content) {
            return [];
        }

        $data = json_decode($content, true);

        return \is_array($data) ? $data : [];
    }

    private function entityManager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }

    private function user(): User
    {
        $this->entityManager()->clear();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => self::EMAIL]);
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }

    private function securityLogCount(string $eventType): int
    {
        $this->entityManager()->clear();

        return $this->entityManager()->getRepository(SecurityLog::class)->count(['eventType' => $eventType]);
    }
}
