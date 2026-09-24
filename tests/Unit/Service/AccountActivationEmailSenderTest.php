<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\AccountActivationEmailSender;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final class AccountActivationEmailSenderTest extends TestCase
{
    public function testSendsInitialPasswordEmail(): void
    {
        $user = $this->createStub(User::class);
        $user->method('getEmail')->willReturn('new-account@example.test');

        $token = new ResetPasswordToken('activation-token', new \DateTimeImmutable('+1 hour'), time());
        $resetPasswordHelper = $this->createMock(ResetPasswordHelperInterface::class);
        $resetPasswordHelper->expects($this->once())
            ->method('generateResetToken')
            ->with($user)
            ->willReturn($token);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) use ($token): bool {
                $this->assertSame('noreply@admds.net', $email->getFrom()[0]->getAddress());
                $this->assertSame('new-account@example.test', $email->getTo()[0]->getAddress());
                $this->assertSame('Créez votre mot de passe', $email->getSubject());
                $this->assertSame('email_notification/account_activation.html.twig', $email->getHtmlTemplate());
                $this->assertSame($token, $email->getContext()['resetToken']);

                return true;
            }));

        new AccountActivationEmailSender($resetPasswordHelper, $mailer)->send($user);
    }
}
