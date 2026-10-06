<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helper;

use App\Entity\Licensee;
use App\Entity\User;
use App\Helper\LicenseeHelper;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mailer\MailerInterface;

final class LicenseeHelperTest extends TestCase
{
    private const string LICENSEE_CODE = '123456';

    private const string ANOTHER_CODE = '654321';

    private const string INVALID_CODE = '999999';

    private \PHPUnit\Framework\MockObject\MockObject $requestStack;

    private \PHPUnit\Framework\MockObject\MockObject $security;

    private \PHPUnit\Framework\MockObject\MockObject $session;

    private LicenseeHelper $licenseeHelper;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->security = $this->createMock(Security::class);
        $mailer = $this->createMock(MailerInterface::class);
        $this->session = $this->createMock(SessionInterface::class);

        $this->licenseeHelper = new LicenseeHelper(
            $this->requestStack,
            $this->security,
            $mailer
        );
    }

    public function testGetLicenseeFromSessionReturnsLicenseeWhenCodeInSession(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);
        $this->session->method('get')->with('selectedLicensee')->willReturn(self::LICENSEE_CODE);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getFftaMemberCode')->willReturn(self::LICENSEE_CODE);

        $user = $this->createMock(User::class);
        $user->method('getLicensees')->willReturn(new ArrayCollection([$licensee]));

        $this->security->method('getUser')->willReturn($user);

        $result = $this->licenseeHelper->getLicenseeFromSession();

        $this->assertSame($licensee, $result);
    }

    public function testGetLicenseeFromSessionReturnsFirstLicenseeWhenNoCodeInSession(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);
        $this->session->method('get')->with('selectedLicensee')->willReturn(null);

        $licensee = $this->createStub(Licensee::class);

        $user = $this->createMock(User::class);
        $user->method('getLicensees')->willReturn(new ArrayCollection([$licensee]));

        $this->security->method('getUser')->willReturn($user);

        $result = $this->licenseeHelper->getLicenseeFromSession();

        $this->assertSame($licensee, $result);
    }

    public function testGetLicenseeFromSessionReturnsNullWhenUserHasNoLicensees(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);

        $user = $this->createMock(User::class);
        $user->method('getLicensees')->willReturn(new ArrayCollection([]));

        $this->security->method('getUser')->willReturn($user);

        $result = $this->licenseeHelper->getLicenseeFromSession();

        $this->assertNotInstanceOf(Licensee::class, $result);
    }

    public function testGetLicenseeFromSessionReturnsFirstWhenCodeNotFound(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);
        $this->session->method('get')->with('selectedLicensee')->willReturn(self::INVALID_CODE);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getFftaMemberCode')->willReturn(self::LICENSEE_CODE);

        $user = $this->createMock(User::class);
        $user->method('getLicensees')->willReturn(new ArrayCollection([$licensee]));

        $this->security->method('getUser')->willReturn($user);

        $result = $this->licenseeHelper->getLicenseeFromSession();

        $this->assertSame($licensee, $result);
    }

    public function testSetSelectedLicenseeStoresCodeInSession(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);

        $licensee = $this->createMock(Licensee::class);
        $licensee->method('getFftaMemberCode')->willReturn(self::LICENSEE_CODE);

        $this->session->expects($this->once())
            ->method('set')
            ->with('selectedLicensee', self::LICENSEE_CODE);

        $this->licenseeHelper->setSelectedLicensee($licensee);
    }

    public function testGetLicenseeFromSessionWithMultipleLicenseesSelectsCorrectOne(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);
        $this->session->method('get')->with('selectedLicensee')->willReturn(self::ANOTHER_CODE);

        $licensee1 = $this->createMock(Licensee::class);
        $licensee1->method('getFftaMemberCode')->willReturn(self::LICENSEE_CODE);

        $licensee2 = $this->createMock(Licensee::class);
        $licensee2->method('getFftaMemberCode')->willReturn(self::ANOTHER_CODE);

        $user = $this->createMock(User::class);
        $user->method('getLicensees')->willReturn(new ArrayCollection([$licensee1, $licensee2]));
        $user->method('hasLicenseeWithCode')->with(self::ANOTHER_CODE)->willReturn(true);
        $user->method('getLicenseeWithCode')->with(self::ANOTHER_CODE)->willReturn($licensee2);

        $this->security->method('getUser')->willReturn($user);

        $result = $this->licenseeHelper->getLicenseeFromSession();

        $this->assertSame($licensee2, $result);
    }

    // ── Stateless mobile API: the licensee comes from the X-Licensee header, never from the session ──

    public function testApiRequestUsesTheLicenseeHeader(): void
    {
        $this->requestStack->expects($this->never())->method('getSession');
        $this->requestStack->method('getCurrentRequest')->willReturn(Request::create('/api/v1/me', server: ['HTTP_X_LICENSEE' => self::ANOTHER_CODE]));

        $first = $this->createStub(Licensee::class);
        $second = $this->createStub(Licensee::class);
        $user = $this->createMock(User::class);
        $user->method('getLicensees')->willReturn(new ArrayCollection([$first, $second]));
        $user->method('getLicenseeWithCode')->with(self::ANOTHER_CODE)->willReturn($second);
        $this->security->method('getUser')->willReturn($user);

        $this->assertSame($second, $this->licenseeHelper->getLicenseeFromSession());
    }

    public function testApiRequestWithoutHeaderUsesTheFirstLicensee(): void
    {
        $this->requestStack->expects($this->never())->method('getSession');
        $first = $this->createStub(Licensee::class);
        $user = $this->createMock(User::class);
        $user->method('getLicensees')->willReturn(new ArrayCollection([$first, $this->createStub(Licensee::class)]));
        $this->security->method('getUser')->willReturn($user);

        $this->requestStack->method('getCurrentRequest')->willReturn(Request::create('/api/v1/me'));
        $this->assertSame($first, $this->licenseeHelper->getLicenseeFromSession());
    }

    public function testApiRequestWithAnEmptyHeaderUsesTheFirstLicensee(): void
    {
        $first = $this->createStub(Licensee::class);
        $user = $this->createMock(User::class);
        $user->method('getLicensees')->willReturn(new ArrayCollection([$first]));
        $this->security->method('getUser')->willReturn($user);
        $this->requestStack->method('getCurrentRequest')->willReturn(Request::create('/api/v1/me', server: ['HTTP_X_LICENSEE' => '']));

        $this->assertSame($first, $this->licenseeHelper->getLicenseeFromSession());
    }

    public function testApiRequestWithALicenseeTheUserDoesNotOwnIsForbidden(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getLicenseeWithCode')->willReturn(null);
        $this->security->method('getUser')->willReturn($user);
        $this->requestStack->method('getCurrentRequest')->willReturn(Request::create('/api/v1/me', server: ['HTTP_X_LICENSEE' => self::INVALID_CODE]));

        $this->expectException(AccessDeniedHttpException::class);
        $this->licenseeHelper->getLicenseeFromSession();
    }

    public function testApiRequestWithoutAnAuthenticatedUserHasNoLicensee(): void
    {
        $this->security->method('getUser')->willReturn(null);
        $this->requestStack->method('getCurrentRequest')->willReturn(Request::create('/api/v1/health'));

        $this->assertNotInstanceOf(Licensee::class, $this->licenseeHelper->getLicenseeFromSession());
    }

    public function testSelectingALicenseeIsANoOpOnApiRequests(): void
    {
        $this->requestStack->expects($this->never())->method('getSession');
        $this->requestStack->method('getCurrentRequest')->willReturn(Request::create('/api/v1/me'));

        $this->licenseeHelper->setSelectedLicensee($this->createStub(Licensee::class));
    }
}
