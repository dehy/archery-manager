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
        /** @var User|null $user */
        $user = $this->security->getUser();

        $request = $this->requestStack->getCurrentRequest();
        if (ApiRequest::is($request)) {
            // No authenticated user (a public API route): there is no licensee to act as.
            return $user instanceof User ? $this->getLicenseeFromHeader($user, $request) : null;
        }

        /** @var User $user */
        return $this->getLicenseeFromWebSession($user);
    }

    public function setSelectedLicensee(?Licensee $licensee): void
    {
        if (ApiRequest::is($this->requestStack->getCurrentRequest())) {
            return;
        }

        // The id, not the FFTA member code: a licensee may not have a code yet (a new applicant, for instance).
        $this->requestStack
            ->getSession()
            ->set(self::SESSION_KEY, $licensee?->getId());
    }

    /**
     * The licensee selected in the session, or the account's first one when none is selected or the
     * selection is not one of the account's licensees (a session from before ids, holding a code, included).
     */
    private function getLicenseeFromWebSession(User $user): ?Licensee
    {
        $licenseeId = $this->requestStack
            ->getSession()
            ->get(self::SESSION_KEY);
        $selected = \is_int($licenseeId) ? $user->getLicenseeWithId($licenseeId) : null;
        if ($selected instanceof Licensee) {
            return $selected;
        }

        $first = $user->getLicensees()->first();
        if (!$first instanceof Licensee) {
            return null;
        }

        $this->setSelectedLicensee($first);

        return $first;
    }

    private function getLicenseeFromHeader(User $user, ?Request $request): ?Licensee
    {
        $code = $request?->headers->get(ApiRequest::HEADER_LICENSEE);
        if (null === $code || '' === $code) {
            return $user->getLicensees()->first() ?: null;
        }

        // The licensee id; an FFTA member code is still accepted (codes always end with a letter, ids are digits).
        $licensee = ctype_digit($code) ? $user->getLicenseeWithId((int) $code) : $user->getLicenseeWithCode($code);

        return $licensee
            ?? throw new AccessDeniedHttpException(\sprintf('Unknown licensee in %s header.', ApiRequest::HEADER_LICENSEE));
    }
}
