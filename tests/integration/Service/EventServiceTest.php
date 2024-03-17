<?php

namespace App\Tests\integration\Service;

use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\GenderType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\LicenseType;
use App\DBAL\Types\RecurringType;
use App\Entity\Club;
use App\Entity\Event;
use App\Entity\EventRecurringPattern;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\User;
use App\Service\EventService;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EventServiceTest extends KernelTestCase
{
    use ReloadDatabaseTrait;

    public function testGetEventsWithInstancesForLicenseeFromDateToDate()
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $license = $this->license_club_2024($this->licensee(), $this->club());

        $weeklyEvent = $this->weeklyTestEvent($this->testEvent());
        $weeklyEvent->setClub($license->getClub());
        $eventService->save($weeklyEvent);

        $EventInstances = $eventService->getEventInstancesForLicenseeFromDateToDate(
            $license->getLicensee(),
            new \DateTimeImmutable('2024-04-01T00:00:00+01:00'),
            new \DateTimeImmutable('2024-04-30T00:00:00+01:00')
        );

        static::assertCount(4, $EventInstances);
        foreach ($EventInstances as $EventInstance) {
            static::assertSame($weeklyEvent, $EventInstance->getEvent());
        }
    }

    public function testGetRecurringInstancesOfNonRecurringEvent(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $testEvent = $this->testEvent();
        $instances = $eventService->getRecurringInstancesDates($testEvent);

        self::assertCount(1, $instances);
        self::assertEquals('2024-03-16', $instances[0]->format('Y-m-d'));
    }

    public function testGetAllRecurringInstancesOfEvent(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $testEvent = $this->testEvent();
        $instances = $eventService->getRecurringInstancesDates($this->weeklyTestEvent($testEvent));

        self::assertCount(7, $instances);
        self::assertEquals('2024-03-16', $instances[0]->format('Y-m-d'));
        self::assertEquals('2024-03-23', $instances[1]->format('Y-m-d'));
        self::assertEquals('2024-03-30', $instances[2]->format('Y-m-d'));
        self::assertEquals('2024-04-06', $instances[3]->format('Y-m-d'));
        self::assertEquals('2024-04-13', $instances[4]->format('Y-m-d'));
        self::assertEquals('2024-04-20', $instances[5]->format('Y-m-d'));
        self::assertEquals('2024-04-27', $instances[6]->format('Y-m-d'));
    }

    public function testGetRecurringInstancesWithEventStartingWithinAndFinishingAfter(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $testEvent = $this->testEvent();
        $instances = $eventService->getRecurringInstancesDates(
            $this->weeklyTestEvent($testEvent),
            new \DateTimeImmutable('2024-03-01'),
            new \DateTimeImmutable('2024-03-31'),
        );

        self::assertCount(3, $instances);
        self::assertEquals('2024-03-16', $instances[0]->format('Y-m-d'));
        self::assertEquals('2024-03-23', $instances[1]->format('Y-m-d'));
        self::assertEquals('2024-03-30', $instances[2]->format('Y-m-d'));
    }

    public function testGetRecurringInstancesWithEventStartingBeforeAndFinishingWithin(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        /** @var EventService $eventService */
        $eventService = $container->get(EventService::class);

        $testEvent = $this->testEvent();
        $instances = $eventService->getRecurringInstancesDates(
            $this->weeklyTestEvent($testEvent),
            new \DateTimeImmutable('2024-04-01'),
            new \DateTimeImmutable('2024-04-30'),
        );

        self::assertCount(4, $instances);
        self::assertEquals('2024-04-06', $instances[0]->format('Y-m-d'));
        self::assertEquals('2024-04-13', $instances[1]->format('Y-m-d'));
        self::assertEquals('2024-04-20', $instances[2]->format('Y-m-d'));
        self::assertEquals('2024-04-27', $instances[3]->format('Y-m-d'));
    }

    protected function testEvent(): Event
    {
        return (new Event())
            ->setName('Test Event')
            ->setStartDate(new \DateTimeImmutable('2024-03-16T00:00:00+01:00'))
            ->setEndDate(new \DateTimeImmutable('2024-03-16T00:00:00+01:00'))
            ->setStartTime(\DateTimeImmutable::createFromFormat('H:i:s', '9:45:00'))
            ->setEndTime(\DateTimeImmutable::createFromFormat('H:i:s', '11:00:00'))
            ->setDiscipline(DisciplineType::INDOOR)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setCreatedBy($this->user())
        ;
    }

    protected function weeklyTestEvent(Event $event): Event
    {
        $recurringPattern = (new EventRecurringPattern())
            ->setEvent($this->testEvent())
            ->setRecurringType(RecurringType::WEEKLY)
            ->setSeparationCount(0)
            ->setDayOfWeek(6)
        ;

        $event
            ->setEndDate(new \DateTimeImmutable('2024-04-27T00:00:00+01:00'))
            ->addRecurringPattern($recurringPattern)
            ->setRecurring(true);

        return $event;
    }

    protected function license_club_2024(Licensee $licensee, Club $club): License
    {
        return (new License())
            ->setLicensee($licensee)
            ->setClub($club)
            ->setSeason(2024)
            ->setType(LicenseType::ADULTES_CLUB)
            ->setActivities([LicenseActivityType::CL])
        ;
    }

    protected function licensee(): Licensee
    {
        return (new Licensee())
            ->setFirstname('Firstname')
            ->setLastname('Lastname')
            ->setGender(GenderType::FEMALE)
            ->setBirthdate(new \DateTimeImmutable('1986-05-12'))
        ;
    }

    protected function user(): User
    {
        return (new User())
            ->setFirstname('Firstname')
            ->setLastname('Lastname')
            ->setGender(GenderType::FEMALE)
            ->setEmail('firstname.lastname@test')
            ->setPassword('test_passwd')
        ;
    }

    protected function club(): Club
    {
        return (new Club())
            ->setName('Test Club')
            ->setCity('Test City')
            ->setPrimaryColor('#FF0000')
            ->setContactEmail('test@test')
            ->setFftaCode('1234567F');
    }
}
