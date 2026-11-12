<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Entity\Club;
use App\Entity\Licensee;
use App\Entity\User;
use App\Helper\EmailHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class EmailHelperTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $mailer;

    private EmailHelper $emailHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->emailHelper = new EmailHelper($this->mailer);
    }

    public function testSendWelcomeEmail(): void
    {
        // Create mock objects
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('licensee@example.com');

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getUser')->willReturn($user);

        $club = $this->createMock(Club::class);
        $club->method('getName')->willReturn('Test Archery Club');
        $club->method('getContactEmail')->willReturn('contact@archeryclub.com');

        // Set up mailer expectations
        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($licensee, $club): bool {
                $toAddresses = $email->getTo();
                $replyToAddresses = $email->getReplyTo();
                $context = $email->getContext();
                $this->assertCount(1, $toAddresses);
                $this->assertSame('licensee@example.com', $toAddresses[0]->getAddress());
                $this->assertCount(1, $replyToAddresses);
                $this->assertSame('contact@archeryclub.com', $replyToAddresses[0]->getAddress());
                $this->assertSame('Test Archery Club - Bienvenue', $email->getSubject());
                $this->assertSame('licensee/mail_account_created.html.twig', $email->getHtmlTemplate());
                $this->assertSame($licensee, $context['licensee']);
                $this->assertSame($club, $context['club']);

                return true;
            }));

        $this->emailHelper->sendWelcomeEmail($licensee, $club);
    }
}
