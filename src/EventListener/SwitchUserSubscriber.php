<?php

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
        $this->licenseeHelper->setSelectedLicensee($user->getLicensees()->first());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::SWITCH_USER => 'onSwitchUser',
        ];
    }
}
