<?php

declare(strict_types=1);

namespace App\Tests\Unit\Scheduler\Handler;

use App\DBAL\Types\LicenseeAttachmentType;
use App\Entity\LicenseeAttachment;
use App\Repository\LicenseeAttachmentRepository;
use App\Scheduler\Handler\SendCaciRemindersHandler;
use App\Scheduler\Message\SendCaciReminders;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @internal
 */
final class SendCaciRemindersHandlerTest extends TestCase
{
    private LicenseeAttachmentRepository&MockObject $attachmentRepository;
    private MailerInterface&MockObject $mailer;
    private EntityManagerInterface&MockObject $entityManager;
    private SendCaciRemindersHandler $handler;

    protected function setUp(): void
    {
        $this->attachmentRepository = $this->createMock(LicenseeAttachmentRepository::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->handler = new SendCaciRemindersHandler(
            $this->attachmentRepository,
            $this->mailer,
            $this->entityManager,
        );
    }

    public function testHandlerWithEmptyResultSet(): void
    {
        $this->attachmentRepository
            ->expects($this->once())
            ->method('findNeedingCaciReminder')
            ->willReturn([]);

        $this->mailer->expects($this->never())->method('send');
        $this->entityManager->expects($this->once())->method('flush');

        $this->handler->__invoke(new SendCaciReminders());
    }

    public function testHandlerSkipsAttachmentWithoutLicenseeUser(): void
    {
        $attachment = new LicenseeAttachment();
        $attachment->setType(LicenseeAttachmentType::MEDICAL_CERTIFICATE);

        $this->attachmentRepository
            ->expects($this->once())
            ->method('findNeedingCaciReminder')
            ->willReturn([$attachment]);

        $this->mailer->expects($this->never())->method('send');
        $this->entityManager->expects($this->once())->method('flush');

        $this->handler->__invoke(new SendCaciReminders());
    }
}

