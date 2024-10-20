<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Licensee;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;

class LicenseeHelper
{
    final public const string SESSION_KEY = 'selectedLicensee';

    protected SessionInterface $session;

    public function __construct(
        protected RequestStack $requestStack,
        protected Security $security,
        protected MailerInterface $mailer,
    ) {
    }

    public function getLicenseeFromSession(): ?Licensee
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $licenseeCode = $this->requestStack
            ->getSession()
            ->get(self::SESSION_KEY);
        if (null !== $licenseeCode && !$user->hasLicenseeWithCode($licenseeCode)) {
            $licenseeCode = null;
        }

        if (null === $licenseeCode) {
            if (0 === $user->getLicensees()->count()) {
                return null;
            }

            $licensee = $user->getLicensees()->first();
            $this->setSelectedLicensee($licensee);

            return $licensee;
        }

        return $user->getLicenseeWithCode($licenseeCode);
    }

    public function setSelectedLicensee(?Licensee $licensee): void
    {
        $this->requestStack
            ->getSession()
            ->set(self::SESSION_KEY, $licensee?->getFftaMemberCode());
    }
}
