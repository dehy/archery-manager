<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security\Api;

use App\Entity\ApiSession;
use App\Entity\User;
use App\Repository\ApiSessionRepository;
use App\Security\Api\ApiTokenManager;
use App\Security\Api\InvalidRefreshTokenException;
use App\Security\Api\RefreshAccountLockedException;
use App\Security\Api\RefreshTokenReuseException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ApiTokenManagerTest extends KernelTestCase
{
    private ApiTokenManager $manager;

    private ApiSessionRepository $sessions;

    private EntityManagerInterface $entityManager;

    private User $user;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->manager = $container->get(ApiTokenManager::class);
        $this->sessions = $container->get(ApiSessionRepository::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->user = $this->userByEmail('clubadmin@ladg.com');
    }

    public function testRetryWithTheRotatedAwayTokenWithinTheGracePeriodIssuesAFreshPair(): void
    {
        $first = $this->manager->issue($this->user);
        $second = $this->manager->refresh($first->refreshToken);

        // The client never got `$second` (lost response) and retries with the old token.
        $third = $this->manager->refresh($first->refreshToken);

        $this->assertNotSame($second->refreshToken, $third->refreshToken);
        $this->assertInstanceOf(\App\Entity\ApiSession::class, $this->manager->findValidSessionByAccessToken($third->accessToken));
        $this->assertNotInstanceOf(\App\Entity\ApiSession::class, $this->manager->findValidSessionByAccessToken($second->accessToken));
        $this->assertSame(0, $this->revokedCount());
    }

    public function testAClientHoldingTheNewestButOneTokenKeepsWorkingInTheGracePeriod(): void
    {
        $first = $this->manager->issue($this->user);
        $second = $this->manager->refresh($first->refreshToken);
        $this->manager->refresh($first->refreshToken);

        // `$second` was the winner of a race and is now the "previous" token.
        $this->manager->refresh($second->refreshToken);

        $this->assertSame(0, $this->revokedCount());
    }

    public function testReuseOutsideTheGracePeriodRevokesTheSession(): void
    {
        $first = $this->manager->issue($this->user);
        $second = $this->manager->refresh($first->refreshToken);
        $this->expireGracePeriod();

        try {
            $this->manager->refresh($first->refreshToken);
            $this->fail('Reusing a rotated refresh token must be rejected.');
        } catch (RefreshTokenReuseException $refreshTokenReuseException) {
            $this->assertSame($this->user->getId(), $refreshTokenReuseException->user->getId());
        }

        $this->assertSame(1, $this->revokedCount());
        $this->expectException(InvalidRefreshTokenException::class);
        $this->manager->refresh($second->refreshToken);
    }

    public function testReusingATokenOfAnAlreadyRevokedSessionIsPlainlyInvalid(): void
    {
        $first = $this->manager->issue($this->user);
        $this->manager->refresh($first->refreshToken);
        $this->manager->revokeByRefreshToken($first->refreshToken);
        $this->expireGracePeriod();

        try {
            $this->manager->refresh($first->refreshToken);
            $this->fail('A revoked session must not refresh.');
        } catch (RefreshTokenReuseException) {
            $this->fail('A revoked session must not be reported as a new token reuse.');
        } catch (InvalidRefreshTokenException) {
            $this->addToAssertionCount(1);
        }
    }

    public function testALockedAccountIsRefusedWithoutLosingItsSession(): void
    {
        $tokens = $this->manager->issue($this->user);
        $this->user->lockAccount(30);
        $this->entityManager->flush();

        try {
            $this->manager->refresh($tokens->refreshToken);
            $this->fail('A locked account must not refresh.');
        } catch (RefreshAccountLockedException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(0, $this->revokedCount());

        $this->user->setAccountLockedUntil(null);
        $this->entityManager->flush();

        $this->assertNotNull($this->manager->refresh($tokens->refreshToken)->accessToken);
    }

    public function testRotationIsAtomicAndRefusesAStaleExpectation(): void
    {
        $tokens = $this->manager->issue($this->user);
        $session = $this->sessions->findOneByRefreshTokenHash(ApiTokenManager::hash($tokens->refreshToken));
        $this->assertInstanceOf(ApiSession::class, $session);
        $now = new \DateTimeImmutable();

        $rotated = $this->sessions->rotateIfUnchanged($session, 'someone-else-rotated-it-first', 'a', $now, 'b', $now, $now);

        $this->assertSame(0, $rotated);
        $this->entityManager->clear();
        $this->assertInstanceOf(\App\Entity\ApiSession::class, $this->sessions->findOneByRefreshTokenHash(ApiTokenManager::hash($tokens->refreshToken)));
    }

    public function testRotationDoesNotTouchARevokedSession(): void
    {
        $tokens = $this->manager->issue($this->user);
        $session = $this->sessions->findOneByRefreshTokenHash(ApiTokenManager::hash($tokens->refreshToken));
        $this->assertInstanceOf(ApiSession::class, $session);
        $this->manager->revoke($session);
        $now = new \DateTimeImmutable();

        $this->assertSame(0, $this->sessions->rotateIfUnchanged($session, $session->getRefreshTokenHash(), 'a', $now, 'b', $now, $now));
    }

    public function testRevokeByRefreshTokenIgnoresUnknownTokensAndRevokedSessions(): void
    {
        $tokens = $this->manager->issue($this->user);

        $this->manager->revokeByRefreshToken('unknown');
        $this->assertSame(0, $this->revokedCount());

        $this->manager->revokeByRefreshToken($tokens->refreshToken);
        $this->manager->revokeByRefreshToken($tokens->refreshToken);
        $this->assertSame(1, $this->revokedCount());
    }

    public function testRevokeAllForUserOnlyAffectsThatUsersLiveSessions(): void
    {
        $this->manager->issue($this->user);
        $this->manager->issue($this->user);

        $already = $this->manager->issue($this->user);
        $this->manager->revokeByRefreshToken($already->refreshToken);
        $other = $this->manager->issue($this->userByEmail('coach@ladg.com'));

        $this->assertSame(2, $this->manager->revokeAllForUser($this->user));

        $this->entityManager->clear();
        $this->assertInstanceOf(\App\Entity\ApiSession::class, $this->manager->findValidSessionByAccessToken($other->accessToken));
        $this->assertSame(3, $this->revokedCount());
    }

    public function testLastUsedIsRecordedAndOnlyRewrittenAfterAMinute(): void
    {
        $tokens = $this->manager->issue($this->user);
        $session = $this->manager->findValidSessionByAccessToken($tokens->accessToken);
        $this->assertInstanceOf(\DateTimeImmutable::class, $session?->getLastUsedAt());

        $firstUse = $session->getLastUsedAt();
        $this->manager->findValidSessionByAccessToken($tokens->accessToken);
        $this->assertEquals($firstUse, $session->getLastUsedAt(), 'A second call within a minute must not rewrite lastUsedAt.');

        $this->entityManager->createQuery('UPDATE '.ApiSession::class.' s SET s.lastUsedAt = :old')
            ->setParameter('old', new \DateTimeImmutable('-5 minutes'))
            ->execute();
        $this->entityManager->clear();

        $refreshed = $this->manager->findValidSessionByAccessToken($tokens->accessToken);
        $this->assertGreaterThan(new \DateTimeImmutable('-1 minute'), $refreshed?->getLastUsedAt());
    }

    private function userByEmail(string $email): User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }

    private function revokedCount(): int
    {
        return (int) $this->entityManager
            ->createQuery('SELECT COUNT(s.id) FROM '.ApiSession::class.' s WHERE s.revokedAt IS NOT NULL')
            ->getSingleScalarResult();
    }

    private function expireGracePeriod(): void
    {
        $this->entityManager->createQuery('UPDATE '.ApiSession::class.' s SET s.rotatedAt = :past')
            ->setParameter('past', new \DateTimeImmutable(\sprintf('-%d seconds', ApiTokenManager::ROTATION_GRACE_SECONDS + 60)))
            ->execute();
        $this->entityManager->clear();
        $this->user = $this->userByEmail('clubadmin@ladg.com');
    }
}
