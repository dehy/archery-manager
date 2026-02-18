<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\SecurityLog;
use App\Entity\User;
use App\Service\FriendlyCaptchaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly RateLimiterFactory $loginLimiter,
        private readonly FriendlyCaptchaService $captchaService,
    ) {
    }

    #[\Override]
    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_username', '');
        $password = $request->request->get('_password', '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // Rate limiting check
        $limiter = $this->loginLimiter->create($request->getClientIp() ?? 'unknown');
        if (false === $limiter->consume(1)->isAccepted()) {
            throw new CustomUserMessageAuthenticationException('Trop de tentatives de connexion. Veuillez réessayer dans quelques minutes.');
        }

        // CAPTCHA validation after 3 failed attempts
        $session = $request->getSession();
        $failedLoginCount = $session->get('failed_login_count', 0);

        if ($failedLoginCount >= 3) {
            $captchaSolution = $request->request->get('frc-captcha-response', '');

            if (empty($captchaSolution) || !$this->captchaService->verify($captchaSolution)) {
                // Log CAPTCHA failure
                $securityLog = new SecurityLog();
                $securityLog->setEmail($email);
                $securityLog->setIpAddress($request->getClientIp() ?? 'unknown');
                $securityLog->setEventType(SecurityLog::EVENT_CAPTCHA_FAILED);
                $securityLog->setUserAgent($request->headers->get('User-Agent', ''));
                $securityLog->setDetails('CAPTCHA verification failed during login attempt');

                $this->entityManager->persist($securityLog);
                $this->entityManager->flush();

                throw new CustomUserMessageAuthenticationException('La vérification de sécurité a échoué. Veuillez réessayer.');
            }
        }

        // Check if password is empty in the form
        if (empty($password)) {
            throw new CustomUserMessageAuthenticationException('Veuillez saisir votre mot de passe.');
        }

        return new Passport(
            new UserBadge($email, function (string $userIdentifier): User {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);

                if (null === $user) {
                    throw new CustomUserMessageAuthenticationException('Email ou mot de passe incorrect.');
                }

                // Check if user has no password set in database
                if (null === $user->getPassword() || '' === $user->getPassword()) {
                    throw new CustomUserMessageAuthenticationException('Votre compte n\'a pas encore de mot de passe. Veuillez utiliser la fonction "Mot de passe oublié" pour en créer un.');
                }

                // Check if account is locked
                if ($user->isAccountLocked()) {
                    $lockedUntil = $user->getAccountLockedUntil();
                    $remainingMinutes = (int) (($lockedUntil->getTimestamp() - time()) / 60);
                    throw new CustomUserMessageAuthenticationException(\sprintf('Votre compte est temporairement verrouillé suite à plusieurs tentatives de connexion échouées. Veuillez réessayer dans %d minutes.', $remainingMinutes));
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    #[\Override]
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Reset failed login attempts and session counter on successful login
        /** @var User $user */
        $user = $token->getUser();
        if ($user instanceof User) {
            $user->resetFailedAttempts();
            $this->entityManager->flush();
        }

        // Reset session failed login count
        $request->getSession()->remove('failed_login_count');

        if (!\in_array($targetPath = $this->getTargetPath($request->getSession(), $firewallName), [null, '', '0'], true)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_homepage'));
    }

    #[\Override]
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
