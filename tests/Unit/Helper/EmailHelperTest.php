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
    private MailerInterface $mailer;
    private LicenseeRepository $licenseeRepository;
    private EmailHelper $emailHelper;

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
            ->with($this->callback(function (TemplatedEmail $email) use ($licensee, $club) {
                $toAddresses = $email->getTo();
                $replyToAddresses = $email->getReplyTo();
                $context = $email->getContext();

                return count($toAddresses) === 1
                    && $toAddresses[0]->getAddress() === 'licensee@example.com'
                    && count($replyToAddresses) === 1
                    && $replyToAddresses[0]->getAddress() === 'contact@archeryclub.com'
                    && $email->getSubject() === 'Test Archery Club - Bienvenue'
                    && $email->getHtmlTemplate() === 'licensee/mail_account_created.html.twig'
                    && $context['licensee'] === $licensee
                    && $context['club'] === $club;
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

        // Set up repository expectations
        $this->licenseeRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive(
                [['fftaId' => [123, 456]]],
                [['fftaId' => [789]]]
            )
            ->willReturnOnConsecutiveCalls(
                [$addedLicensee1, $addedLicensee2],
                [$updatedLicensee]
            );

        // Set up mailer expectations
        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($addedLicensee1, $addedLicensee2, $updatedLicensee) {
                $toAddresses = $email->getTo();
                $context = $email->getContext();

                return count($toAddresses) === 2
                    && $toAddresses[0] instanceof Address
                    && $toAddresses[0]->getAddress() === 'admin1@example.com'
                    && $toAddresses[0]->getName() === 'Admin One'
                    && $toAddresses[1] instanceof Address
                    && $toAddresses[1]->getAddress() === 'admin2@example.com'
                    && $toAddresses[1]->getName() === 'Admin Two'
                    && $email->getSubject() === 'Synchronisation FFTA'
                    && $email->getHtmlTemplate() === 'email_notification/updated_licensees.txt.twig'
                    && $email->getTextTemplate() === 'email_notification/updated_licensees.txt.twig'
                    && $context['count'] === 4 // 2 created + 1 updated + 1 removed
                    && $context['added'] === [$addedLicensee1, $addedLicensee2]
                    && $context['updated'] === [$updatedLicensee];
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

        // Repository should still be called but with empty arrays
        $this->licenseeRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->withConsecutive(
                [['fftaId' => []]],
                [['fftaId' => []]]
            )
            ->willReturn([]);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) {
                $context = $email->getContext();
                return $context['count'] === 0
                    && $context['added'] === []
                    && $context['updated'] === [];
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
            ->with($this->callback(function (TemplatedEmail $email) {
                $toAddresses = $email->getTo();
                return count($toAddresses) === 1
                    && $toAddresses[0]->getAddress() === 'single@example.com';
            }));

        $this->emailHelper->sendLicenseesSyncResults($toEmails, $syncResults);
    }
}
