<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\AbstractApiTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class EmailVerificationTest extends AbstractApiTestCase
{
    use ResetDatabase, Factories;

    public function testUserRegistrationGeneratesVerificationToken(): void
    {
        $client = self::createClient();

        // Register a new user
        $response = $client->request('POST', '/register', [
            'json' => [
                'email' => 'verification@example.com',
                'password' => 'SecurePassword123!',
                'givenName' => 'John',
                'familyName' => 'Doe',
                'gender' => 'male'
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        // Verify user was created with verification token
        $userRepository = self::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'verification@example.com']);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertFalse($user->isVerified);
        $this->assertNotNull($user->getEmailVerificationToken());
        $this->assertNotNull($user->getEmailVerificationTokenExpiresAt());
        
        // Token should expire in the future (e.g., 24 hours)
        $this->assertGreaterThan(new \DateTimeImmutable(), $user->getEmailVerificationTokenExpiresAt());
    }

    public function testEmailVerificationWithValidToken(): void
    {
        $client = self::createClient();

        // First, register a user to get a verification token
        $client->request('POST', '/register', [
            'json' => [
                'email' => 'verify@example.com',
                'password' => 'SecurePassword123!',
                'givenName' => 'Jane',
                'familyName' => 'Smith',
                'gender' => 'female'
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);

        // Get the user and token from database
        $userRepository = self::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'verify@example.com']);
        $token = $user->getEmailVerificationToken();

        // Verify the email with the token
        $response = $client->request('POST', '/verify-email', [
            'json' => [
                'token' => $token
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        
        $this->assertJsonContains([
            'verified' => true,
            'message' => 'Email verified successfully',
        ]);

        // Verify user is now verified in database
        $userRepository = self::getContainer()->get('doctrine')->getRepository(User::class);
        $verifiedUser = $userRepository->findOneBy(['email' => 'verify@example.com']);
        $this->assertTrue($verifiedUser->isVerified);
        $this->assertNull($verifiedUser->getEmailVerificationToken()); // Token should be cleared
        $this->assertNull($verifiedUser->getEmailVerificationTokenExpiresAt());
    }

    public function testEmailVerificationWithInvalidToken(): void
    {
        $client = self::createClient();

        $client->request('POST', '/verify-email', [
            'json' => [
                'token' => 'invalid-token-12345'
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        
        $this->assertJsonContains([
            '@type' => 'Error',
            'status' => 422,
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testEmailVerificationWithExpiredToken(): void
    {
        $client = self::createClient();

        // Register a user
        $client->request('POST', '/register', [
            'json' => [
                'email' => 'expired@example.com',
                'password' => 'SecurePassword123!',
                'givenName' => 'Bob',
                'familyName' => 'Wilson',
                'gender' => 'male'
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        // Manually expire the token for testing
        $userRepository = self::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'expired@example.com']);
        $token = $user->getEmailVerificationToken();
        
        // Set expiration to the past
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $user->setEmailVerificationTokenExpiresAt(new \DateTimeImmutable('-1 hour'));
        $entityManager->flush();

        // Try to verify with expired token
        $response = $client->request('POST', '/verify-email', [
            'json' => [
                'token' => $token
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        
        $data = $response->toArray(false);
        $this->assertStringContainsString('Invalid or expired verification token', $data['detail']);
        
        // User should still be unverified
        $userRepository = self::getContainer()->get('doctrine')->getRepository(User::class);
        $expiredUser = $userRepository->findOneBy(['email' => 'expired@example.com']);
        $this->assertFalse($expiredUser->isVerified);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testEmailVerificationForAlreadyVerifiedUser(): void
    {
        $client = self::createClient();

        // Register and immediately verify a user
        $client->request('POST', '/register', [
            'json' => [
                'email' => 'already@verified.com',
                'password' => 'SecurePassword123!',
                'givenName' => 'Alice',
                'familyName' => 'Johnson',
                'gender' => 'female'
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ]
        ]);

        $userRepository = self::getContainer()->get('doctrine')->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'already@verified.com']);
        $token = $user->getEmailVerificationToken();

        // Verify the user first time
        $client->request('POST', '/verify-email', [
            'json' => ['token' => $token],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        // Try to verify again with the same token
        $response = $client->request('POST', '/verify-email', [
            'json' => ['token' => $token],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        
        $data = $response->toArray(false);
        $this->assertStringContainsString('Invalid or expired verification token', $data['detail']);
    }
}
