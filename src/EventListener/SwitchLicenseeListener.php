<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Licensee;
use App\Entity\User;
use App\Helper\LicenseeHelper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event: 'kernel.request')]
class SwitchLicenseeListener
{
    public function __construct(
        protected Security $security,
        protected LicenseeHelper $licenseeHelper,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // The licensee id (the switch links in the menus and the admin "impersonate" action send it).
        $licenseeId = $request->query->get('_switch_licensee');

        if (null === $licenseeId || '' === $licenseeId) {
            return;
        }

        /** @var User|null $user */
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $licensee = ctype_digit($licenseeId) ? $user->getLicenseeWithId((int) $licenseeId) : null;
        if ($licensee instanceof Licensee) {
            $this->licenseeHelper->setSelectedLicensee($licensee);
        }

        $request->query->remove('_switch_licensee');
        $request->server->set(
            'QUERY_STRING',
            http_build_query($request->query->all(), '', '&'),
        );

        $response = new RedirectResponse($request->getUri(), Response::HTTP_FOUND);

        $event->setResponse($response);
    }
}
