<?php

declare(strict_types=1);

namespace App\Tests\Unit\Scheduler;

use App\Entity\Club;
use App\Repository\ClubRepository;
use App\Scheduler\FftaLicenseesProvider;
use App\Scheduler\Message\SyncFftaLicensees;
use App\Tests\MakePropertyAccessibleTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;

final class FftaLicenseesProviderTest extends TestCase
{
    use MakePropertyAccessibleTrait;

    /**
     * @throws \ReflectionException
     */
    public function testSchedule(): void
    {
        $club1 = (new Club())->setFftaCode(41);
        $this->set($club1, 1);
        $club2 = (new Club())->setFftaCode(42);
        $this->set($club2, 2);

        $clubRepository = $this->createMock(ClubRepository::class);
        $clubRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$club1, $club2]);

        $provider = new FftaLicenseesProvider($clubRepository);
        $schedule = $provider->getSchedule();

        $this->assertCount(1, $schedule->getRecurringMessages());
        $recurringMessage = $schedule->getRecurringMessages()[0];

        $this->assertInstanceOf(CronExpressionTrigger::class, $recurringMessage->getTrigger());

        $context = new MessageContext(
            'test',
            1,
            $recurringMessage->getTrigger(),
            new \DateTimeImmutable(),
        );
        $messages = iterator_to_array($recurringMessage->getMessages($context));
        $this->assertCount(2, $messages);
        $message1 = $messages[0];
        $this->assertInstanceOf(SyncFftaLicensees::class, $message1);
        $this->assertSame(1, $message1->getId());
        $this->assertSame('41', $message1->getClubCode());
        $this->assertSame(2025, $message1->getSeason());

        $message2 = $messages[1];
        $this->assertInstanceOf(SyncFftaLicensees::class, $message2);
        $this->assertSame(2, $message2->getId());
        $this->assertSame('42', $message2->getClubCode());
        $this->assertSame(2025, $message2->getSeason());
    }
}
