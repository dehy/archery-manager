<?php

declare(strict_types=1);

namespace App\Tests\Functional\Email;

use App\Entity\User;
use App\Tests\AbstractApiTestCase;

class EmailIntegrationTest extends AbstractApiTestCase
{
    public function testUserRegistrationSendsVerificationEmail(): void
    {
        // Arrange - use unique email to avoid conflicts
        $uniqueEmail = 'emailintegration' . time() . '@example.com';
        $userData = [
            'email' => $uniqueEmail,
            'password' => 'Password123!',
            'givenName' => 'John',
            'familyName' => 'Doe',
            'gender' => 'male',
            'telephone' => '+33123456789',
        ];

        // Create client and enable profiler to capture emails
        $client = self::createClient();
        $client->enableProfiler();

        // Act
        $response = $client->request('POST', '/register', [
            'json' => $userData,
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);

        // Assert response
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $this->assertJsonContains([
            'email' => $uniqueEmail,
            'givenName' => 'John',
            'familyName' => 'Doe',
            'gender' => 'male',
            'isVerified' => false,
        ]);

        // Verify that the user was created with a verification token
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $uniqueEmail]);
        
        $this->assertNotNull($user, 'User should be created');
        $this->assertNotNull($user->getEmailVerificationToken(), 'User should have a verification token');
        $this->assertNotNull($user->getEmailVerificationTokenExpiresAt(), 'User should have a token expiration date');
        $this->assertFalse($user->isVerified, 'User should start unverified');
        
        // Verify that an email was sent
        $mailCollector = $client->getProfile()->getCollector('mailer');
        $messages = $mailCollector->getEvents()->getMessages();
        $this->assertCount(1, $messages, 'Exactly one email should have been sent');
        $message = $messages[0];
        
        // Verify email properties
        $fromAddresses = [];
        foreach ($message->getFrom() as $address) {
            $fromAddresses[] = $address->getAddress();
        }
        $toAddresses = [];
        foreach ($message->getTo() as $address) {
            $toAddresses[] = $address->getAddress();
        }
        
        $this->assertEquals(['noreply@test.archerymanager.com'], $fromAddresses);
        $this->assertEquals([$uniqueEmail], $toAddresses);
        $this->assertEquals('Verify your email address', $message->getSubject());
        
        // Verify the email contains the verification token and user name
        $htmlBody = $message->getHtmlBody();
        $this->assertStringContainsString($user->getEmailVerificationToken(), $htmlBody);
        $this->assertStringContainsString('John Doe', $htmlBody);
        $this->assertStringContainsString('verify', $htmlBody);
    }
}
