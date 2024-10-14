<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use App\Helper\LicenseeHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SwitchUserSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly LicenseeHelper $licenseeHelper)
    {
    }

    public function onSwitchUser(SwitchUserEvent $event): void
    {
        /** @var User $user */
        $user = $event->getTargetUser();
        $licensee = $user->getLicensees()->count() > 0 ? $user->getLicensees()->first() : null;
        $this->licenseeHelper->setSelectedLicensee($licensee);
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::SWITCH_USER => 'onSwitchUser',
        ];
    }
}
