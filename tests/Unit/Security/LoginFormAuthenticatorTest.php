<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class LoginFormAuthenticatorTest extends TestCase
{
    private UrlGeneratorInterface $urlGenerator;
    private EntityManagerInterface $entityManager;
    private LoginFormAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route) => match ($route) {
                'app_login' => '/login',
                'app_homepage' => '/',
                default => '/'.$route,
            }
        );

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->authenticator = new LoginFormAuthenticator($this->urlGenerator, $this->entityManager);
    }

    public function testAuthenticateWithEmptyPasswordThrowsException(): void
    {
        $request = $this->createRequest('test@example.com', '');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Veuillez saisir votre mot de passe.');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateReturnsPassportForValidCredentials(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed_password');

        $this->setupUserRepository($user);

        $request = $this->createRequest('test@example.com', 'password123');
        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(Passport::class, $passport);
    }

    public function testAuthenticateWithNonExistentUserThrowsException(): void
    {
        $this->setupUserRepository(null);

        $request = $this->createRequest('nonexistent@example.com', 'password123');
        $passport = $this->authenticator->authenticate($request);

        // The exception is thrown inside the UserBadge callback, which runs lazily
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Email ou mot de passe incorrect.');

        // Trigger the user loader
        $passport->getUser();
    }

    public function testAuthenticateWithNoPasswordUserThrowsException(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        // Password is null by default

        $this->setupUserRepository($user);

        $request = $this->createRequest('test@example.com', 'password123');
        $passport = $this->authenticator->authenticate($request);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Votre compte n\'a pas encore de mot de passe');

        $passport->getUser();
    }

    public function testAuthenticateWithEmptyPasswordUserThrowsException(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('');

        $this->setupUserRepository($user);

        $request = $this->createRequest('test@example.com', 'password123');
        $passport = $this->authenticator->authenticate($request);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Votre compte n\'a pas encore de mot de passe');

        $passport->getUser();
    }

    public function testOnAuthenticationSuccessRedirectsToHomepage(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturn(null);

        $request = new Request();
        $request->setSession($session);

        $token = $this->createMock(TokenInterface::class);

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessRedirectsToTargetPath(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturn('/dashboard');

        $request = new Request();
        $request->setSession($session);

        $token = $this->createMock(TokenInterface::class);

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/dashboard', $response->getTargetUrl());
    }

    private function createRequest(string $email, string $password): Request
    {
        $session = $this->createMock(SessionInterface::class);

        $request = new Request([], [
            '_username' => $email,
            '_password' => $password,
            '_csrf_token' => 'test_csrf_token',
        ]);
        $request->setSession($session);

        return $request;
    }

    private function setupUserRepository(?User $user): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($user);

        $this->entityManager->method('getRepository')->with(User::class)->willReturn($repository);
    }
}
