<?php

namespace App\Security;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\LogicException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class TokenStorageDecorator implements TokenStorageInterface, ServiceSubscriberInterface
{
    protected static ?TokenInterface $decoratedToken = null;
    private bool $enableUsageTracking = false;

    public function __construct(private readonly TokenStorageInterface $storage, private readonly ContainerInterface $container)
    {
    }

    public function getToken(): ?TokenInterface
    {
        if ($this->shouldTrackUsage()) {
            // increments the internal session usage index
            $this->getSession()->getMetadataBag();
        }

        return static::$decoratedToken ?? $this->storage->getToken();
    }

    public function setToken(TokenInterface $token = null): void
    {
        static::$decoratedToken = $token;

        $this->storage->setToken($token);

        if ($token && $this->shouldTrackUsage()) {
            // increments the internal session usage index
            $this->getSession()->getMetadataBag();
        }
    }

    public function setUser(UserInterface $user, string $tokenClass = UsernamePasswordToken::class): void
    {
        if (static::$decoratedToken) {
            static::$decoratedToken->setUser($user);
        } else {
            static::getNewToken($user, $tokenClass);
        }

        $this->setToken(static::$decoratedToken);
    }

    public static function getNewToken(
        UserInterface $user,
        string $tokenClass = UsernamePasswordToken::class,
        string $firewall = 'firewall.main',
    ): TokenInterface {
        switch ($tokenClass) {
            case PostAuthenticationToken::class :
            case UsernamePasswordToken::class :
                static::$decoratedToken = new $tokenClass($user, $firewall, $user->getRoles());
                break;
            default:
                throw new LogicException(sprintf('The token %s is not supported', $tokenClass));
        }

        return static::$decoratedToken;
    }

    public function enableUsageTracking(): void
    {
        $this->enableUsageTracking = true;
    }

    public function disableUsageTracking(): void
    {
        $this->enableUsageTracking = false;
    }

    public static function getSubscribedServices(): array
    {
        return [
            'request_stack' => RequestStack::class,
        ];
    }

    private function getSession(): SessionInterface
    {
        return $this->container->get('request_stack')->getSession();
    }

    private function shouldTrackUsage(): bool
    {
        return $this->enableUsageTracking && $this->container->get('request_stack')->getMainRequest();
    }
}
