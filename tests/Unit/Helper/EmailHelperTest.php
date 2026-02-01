<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Entity\Club;
use App\Entity\Licensee;
use App\Entity\User;
use App\Helper\EmailHelper;
use App\Helper\SyncReturnValues;
use App\Repository\LicenseeRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class EmailHelperTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $mailer;

    private \PHPUnit\Framework\MockObject\MockObject $licenseeRepository;

    private EmailHelper $emailHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->licenseeRepository = $this->createMock(LicenseeRepository::class);
        $this->emailHelper = new EmailHelper($this->mailer, $this->licenseeRepository);
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

    public function testSendLicenseesSyncResults(): void
    {
        // Create mock users
        $user1 = $this->createMock(User::class);
        $user1->method('getEmail')->willReturn('admin1@example.com');
        $user1->method('getFullname')->willReturn('Admin One');

        $user2 = $this->createMock(User::class);
        $user2->method('getEmail')->willReturn('admin2@example.com');
        $user2->method('getFullname')->willReturn('Admin Two');

        $toEmails = [$user1, $user2];

        // Mock sync results
        $syncResults = [
            SyncReturnValues::CREATED->value => [123, 456],
            SyncReturnValues::UPDATED->value => [789],
            SyncReturnValues::REMOVED->value => [101112],
        ];

        // Mock licensees
        $addedLicensee1 = $this->createMock(Licensee::class);
        $addedLicensee2 = $this->createMock(Licensee::class);
        $updatedLicensee = $this->createMock(Licensee::class);

        $invocationCount = 0;

        // Set up repository expectations
        $this->licenseeRepository
            ->expects($this->exactly(2))
            ->method('findBy')->willReturnCallback(function (...$parameters) use (&$invocationCount, $addedLicensee1, $addedLicensee2, $updatedLicensee): array {
                ++$invocationCount;
                if (1 === $invocationCount) {
                    $this->assertSame(['fftaId' => [123, 456]], $parameters[0]);

                    return [$addedLicensee1, $addedLicensee2];
                }

                if (2 === $invocationCount) {
                    $this->assertSame(['fftaId' => [789]], $parameters[0]);

                    return [$updatedLicensee];
                }

                return [];
            });

        // Set up mailer expectations
        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($addedLicensee1, $addedLicensee2, $updatedLicensee): bool {
                $toAddresses = $email->getTo();
                $context = $email->getContext();
                $this->assertCount(2, $toAddresses);
                $this->assertInstanceOf(Address::class, $toAddresses[0]);
                $this->assertSame('admin1@example.com', $toAddresses[0]->getAddress());
                $this->assertSame('Admin One', $toAddresses[0]->getName());
                $this->assertInstanceOf(Address::class, $toAddresses[1]);
                $this->assertSame('admin2@example.com', $toAddresses[1]->getAddress());
                $this->assertSame('Admin Two', $toAddresses[1]->getName());
                $this->assertSame('Synchronisation FFTA', $email->getSubject());
                $this->assertSame('email_notification/updated_licensees.txt.twig', $email->getHtmlTemplate());
                $this->assertSame('email_notification/updated_licensees.txt.twig', $email->getTextTemplate());
                $this->assertSame(4, $context['count']);
                $this->assertSame([$addedLicensee1, $addedLicensee2], $context['added']);
                $this->assertSame([$updatedLicensee], $context['updated']);

                return true;
            }));

        $this->emailHelper->sendLicenseesSyncResults($toEmails, $syncResults);
    }

    public function testSendLicenseesSyncResultsWithEmptyResults(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('admin@example.com');
        $user->method('getFullname')->willReturn('Admin');

        $toEmails = [$user];
        $syncResults = [
            SyncReturnValues::CREATED->value => [],
            SyncReturnValues::UPDATED->value => [],
            SyncReturnValues::REMOVED->value => [],
        ];

        $invocationCount = 0;

        // Repository should still be called but with empty arrays
        $this->licenseeRepository
            ->expects($this->exactly(2))
            ->method('findBy')->willReturnCallback(function (...$parameters) use (&$invocationCount): array {
                ++$invocationCount;
                if (1 === $invocationCount) {
                    $this->assertSame(['fftaId' => []], $parameters[0]);
                }

                if (2 === $invocationCount) {
                    $this->assertSame(['fftaId' => []], $parameters[0]);
                }

                return [];
            });

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email): bool {
                $context = $email->getContext();
                $this->assertSame(0, $context['count']);
                $this->assertSame($context['added'], []);
                $this->assertSame($context['updated'], []);

                return true;
            }));

        $this->emailHelper->sendLicenseesSyncResults($toEmails, $syncResults);
    }

    public function testSendLicenseesSyncResultsWithSingleUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('single@example.com');
        $user->method('getFullname')->willReturn('Single User');

        $toEmails = [$user];
        $syncResults = [
            SyncReturnValues::CREATED->value => [999],
            SyncReturnValues::UPDATED->value => [],
            SyncReturnValues::REMOVED->value => [],
        ];

        $addedLicensee = $this->createMock(Licensee::class);

        $this->licenseeRepository
            ->method('findBy')
            ->willReturnOnConsecutiveCalls([$addedLicensee], []);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email): bool {
                $toAddresses = $email->getTo();
                $this->assertCount(1, $toAddresses);
                $this->assertSame('single@example.com', $toAddresses[0]->getAddress());

                return true;
            }));

        $this->emailHelper->sendLicenseesSyncResults($toEmails, $syncResults);
    }
}
