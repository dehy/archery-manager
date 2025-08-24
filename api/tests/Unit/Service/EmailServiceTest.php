<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\EmailService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailServiceTest extends TestCase
{
    private MailerInterface $mailer;
    private Environment $twig;
    private LoggerInterface $logger;
    private EmailService $emailService;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->emailService = new EmailService(
            $this->mailer,
            $this->twig,
            $this->logger,
            'test@example.com'
        );
    }

    public function testSendEmailVerificationSendsEmailWithCorrectParameters(): void
    {
        // Arrange
        $user = $this->createMock(User::class);
        $user->email = 'test@example.com';
        
        $verificationToken = 'mock-verification-token-123';
        
        $user->method('getEmailVerificationToken')
            ->willReturn($verificationToken);

        $renderedTemplate = '<html><body>Verification email content</body></html>';

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'email/verification.html.twig',
                [
                    'user' => $user,
                    'verificationToken' => $verificationToken,
                ]
            )
            ->willReturn($renderedTemplate);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($renderedTemplate): bool {
                return $email->getTo()[0]->getAddress() === 'test@example.com'
                    && $email->getSubject() === 'Verify your email address'
                    && $email->getHtmlBody() === $renderedTemplate
                    && $email->getFrom()[0]->getAddress() === 'test@example.com';
            }));

        $this->logger
            ->expects($this->exactly(2))
            ->method('info');

        // Act
        $this->emailService->sendEmailVerification($user);

        // Assert
        // Assertions are handled by the mock expectations above
        $this->assertTrue(true); // PHPUnit requires at least one assertion
    }

    public function testSendEmailVerificationThrowsExceptionWhenUserHasNoEmail(): void
    {
        // Arrange
        $user = $this->createMock(User::class);
        $user->email = ''; // Empty email
        
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User must have an email address');

        $this->emailService->sendEmailVerification($user);
    }

    public function testSendEmailVerificationThrowsExceptionWhenUserHasNoVerificationToken(): void
    {
        // Arrange
        $user = $this->createMock(User::class);
        $user->email = 'test@example.com';
        
        $user->method('getEmailVerificationToken')
            ->willReturn(null); // No verification token

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User must have an email verification token');

        $this->emailService->sendEmailVerification($user);
    }
}
