<?php

namespace App\Helper;

use App\Entity\Licensee;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LicenseeHelper
{
    final public const SESSION_KEY = 'selectedLicensee';

    protected SessionInterface $session;

    public function __construct(
        protected RequestStack $requestStack,
        protected Security $security,
    ) {
    }

    public function getLicenseeFromSession(): Licensee
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $licenseeCode = $this->requestStack
            ->getSession()
            ->get(self::SESSION_KEY);
        if (null !== $licenseeCode || !$user->getLicensees()->containsKey($licenseeCode)) {
            $licenseeCode = null;
        }
        if (null === $licenseeCode) {
            $licensee = $user->getLicensees()->first();
            $this->setSelectedLicensee($licensee);

            return $licensee;
        }
        foreach ($user->getLicensees() as $licensee) {
            if ($licensee->getFftaMemberCode() === $licenseeCode) {
                return $licensee;
            }
        }

        throw new \LogicException('Should have get a licensee.');
    }

    public function setSelectedLicensee(Licensee $licensee): void
    {
        $this->requestStack
            ->getSession()
            ->set(self::SESSION_KEY, $licensee->getFftaMemberCode());
    }
}
