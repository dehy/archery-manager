<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Enum\LicenseeImportNotificationScenario;
use App\Service\LicenseeImportNotificationEmailSender;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

final class LicenseeImportNotificationEmailSenderTest extends TestCase
{
    /**
     * @return iterable<string, array{LicenseeImportNotificationScenario, string, string}>
     */
    public static function scenarioProvider(): iterable
    {
        yield 'new account' => [
            LicenseeImportNotificationScenario::NewAccount,
            'Votre compte a été créé',
            'email_notification/licensee_import_new_account.html.twig',
        ];
        yield 'renewal' => [
            LicenseeImportNotificationScenario::Renewal,
            'Votre licence a été synchronisée',
            'email_notification/licensee_import_renewal.html.twig',
        ];
        yield 'welcome to club' => [
            LicenseeImportNotificationScenario::WelcomeToClub,
            'Bienvenue au club !',
            'email_notification/licensee_import_welcome_to_club.html.twig',
        ];
    }

    /**
     * @dataProvider scenarioProvider
     */
    public function testSendsNotificationEmailForScenario(
        LicenseeImportNotificationScenario $scenario,
        string $expectedSubject,
        string $expectedTemplate,
    ): void {
        $user = $this->createStub(User::class);
        $user->method('getEmail')->willReturn('licensee@example.test');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($user, $expectedSubject, $expectedTemplate): bool {
                $this->assertSame('noreply@admds.net', $email->getFrom()[0]->getAddress());
                $this->assertSame('licensee@example.test', $email->getTo()[0]->getAddress());
                $this->assertSame($expectedSubject, $email->getSubject());
                $this->assertSame($expectedTemplate, $email->getHtmlTemplate());
                $this->assertSame($user, $email->getContext()['user']);

                return true;
            }));

        new LicenseeImportNotificationEmailSender($mailer)->send($user, $scenario);
    }
}
