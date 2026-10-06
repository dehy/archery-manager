<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Api;

use App\Security\Api\ApiLoginFailureHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class ApiLoginFailureHandlerTest extends TestCase
{
    public function testThrottledLoginIsTooManyRequestsWithARetryDelay(): void
    {
        $response = new ApiLoginFailureHandler()->onAuthenticationFailure(new Request(), new TooManyLoginAttemptsAuthenticationException(15));

        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode(), (string) $response->getContent());
        $this->assertSame('900', $response->headers->get('Retry-After'));
        $this->assertSame('too_many_attempts', $this->body($response)['error']);
    }

    public function testLockedAccountIsReportedWithItsMessage(): void
    {
        $response = new ApiLoginFailureHandler()->onAuthenticationFailure(new Request(), new CustomUserMessageAccountStatusException('Account temporarily locked. Try again in 5 minutes.'));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode(), (string) $response->getContent());
        $this->assertSame('account_locked', $this->body($response)['error']);
        $this->assertSame('Account temporarily locked. Try again in 5 minutes.', $this->body($response)['message']);
    }

    public function testUnknownUserAndBadPasswordAreIndistinguishable(): void
    {
        $handler = new ApiLoginFailureHandler();

        $unknown = $handler->onAuthenticationFailure(new Request(), new UserNotFoundException());
        $badPassword = $handler->onAuthenticationFailure(new Request(), new BadCredentialsException());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $unknown->getStatusCode(), (string) $unknown->getContent());
        $this->assertSame($unknown->getContent(), $badPassword->getContent());
        $this->assertSame('invalid_credentials', $this->body($unknown)['error']);
    }

    /**
     * @return array<string, string>
     */
    private function body(Response $response): array
    {
        return json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }
}
