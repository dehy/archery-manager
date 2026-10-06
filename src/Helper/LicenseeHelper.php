<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Licensee;
use App\Entity\User;
use App\Security\Api\ApiRequest;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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

    /**
     * The licensee the current user acts as: from the session on the web,
     * from the X-Licensee header on the stateless mobile API.
     */
    public function getLicenseeFromSession(): ?Licensee
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $request = $this->requestStack->getCurrentRequest();
        if (ApiRequest::is($request)) {
            return $this->getLicenseeFromHeader($user, $request);
        }

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
        if (ApiRequest::is($this->requestStack->getCurrentRequest())) {
            return;
        }

        $this->requestStack
            ->getSession()
            ->set(self::SESSION_KEY, $licensee?->getFftaMemberCode());
    }

    private function getLicenseeFromHeader(User $user, ?Request $request): ?Licensee
    {
        $code = $request?->headers->get(ApiRequest::HEADER_LICENSEE);
        if (null === $code || '' === $code) {
            return $user->getLicensees()->first() ?: null;
        }

        return $user->getLicenseeWithCode($code)
            ?? throw new AccessDeniedHttpException(\sprintf('Unknown licensee in %s header.', ApiRequest::HEADER_LICENSEE));
    }
}
