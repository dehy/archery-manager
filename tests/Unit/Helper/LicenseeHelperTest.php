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
    private const int LICENSEE_ID = 11;

    private const int ANOTHER_ID = 22;

    private const int FOREIGN_ID = 99;

    private const string ANOTHER_CODE = '0654321B';

    private const string INVALID_CODE = '0999999Z';

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

    public function testGetLicenseeFromSessionReturnsTheLicenseeSelectedInSession(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);
        $this->session->method('get')->with('selectedLicensee')->willReturn(self::LICENSEE_ID);

        $licensee = $this->licensee(self::LICENSEE_ID);
        $this->user([$licensee]);

        $this->assertSame($licensee, $this->licenseeHelper->getLicenseeFromSession());
    }

    public function testGetLicenseeFromSessionReturnsFirstLicenseeWhenNothingIsSelected(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);
        $this->session->method('get')->with('selectedLicensee')->willReturn(null);

        $licensee = $this->licensee(self::LICENSEE_ID);
        $this->user([$licensee]);

        $this->assertSame($licensee, $this->licenseeHelper->getLicenseeFromSession());
    }

    public function testGetLicenseeFromSessionReturnsNullWhenUserHasNoLicensees(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);
        $this->user([]);

        $this->assertNotInstanceOf(Licensee::class, $this->licenseeHelper->getLicenseeFromSession());
    }

    public function testGetLicenseeFromSessionFallsBackToTheFirstWhenTheSelectionIsNotOneOfTheAccount(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);
        $this->session->method('get')->with('selectedLicensee')->willReturn(self::FOREIGN_ID);

        $licensee = $this->licensee(self::LICENSEE_ID);
        $this->user([$licensee]);
        $this->session->expects($this->once())->method('set')->with('selectedLicensee', self::LICENSEE_ID);

        $this->assertSame($licensee, $this->licenseeHelper->getLicenseeFromSession());
    }

    public function testASessionFromBeforeIdsHoldingAnFftaCodeFallsBackToTheFirst(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);
        // A code made of digits must not be mistaken for an id.
        $this->session->method('get')->with('selectedLicensee')->willReturn((string) self::ANOTHER_ID);

        $first = $this->licensee(self::LICENSEE_ID);
        $this->user([$first, $this->licensee(self::ANOTHER_ID)]);

        $this->assertSame($first, $this->licenseeHelper->getLicenseeFromSession());
    }

    public function testSetSelectedLicenseeStoresTheIdInSession(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);

        $this->session->expects($this->once())
            ->method('set')
            ->with('selectedLicensee', self::LICENSEE_ID);

        $this->licenseeHelper->setSelectedLicensee($this->licensee(self::LICENSEE_ID));
    }

    public function testALicenseeWithoutFftaCodeCanBeSelected(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);
        $this->session->method('get')->with('selectedLicensee')->willReturn(self::ANOTHER_ID);

        $applicant = new Licensee();
        $this->setId($applicant, self::ANOTHER_ID);
        $this->user([$this->licensee(self::LICENSEE_ID), $applicant]);

        $this->assertNull($applicant->getFftaMemberCode());
        $this->assertSame($applicant, $this->licenseeHelper->getLicenseeFromSession());
    }

    public function testGetLicenseeFromSessionWithMultipleLicenseesSelectsCorrectOne(): void
    {
        $this->requestStack->method('getSession')->willReturn($this->session);
        $this->session->method('get')->with('selectedLicensee')->willReturn(self::ANOTHER_ID);

        $second = $this->licensee(self::ANOTHER_ID);
        $this->user([$this->licensee(self::LICENSEE_ID), $second]);

        $this->assertSame($second, $this->licenseeHelper->getLicenseeFromSession());
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

    public function testApiRequestSelectsTheLicenseeByIdIncludingOneWithoutFftaCode(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(Request::create('/api/v1/me', server: ['HTTP_X_LICENSEE' => (string) self::ANOTHER_ID]));

        $applicant = new Licensee();
        $this->setId($applicant, self::ANOTHER_ID);
        $this->user([$this->licensee(self::LICENSEE_ID), $applicant]);

        $this->assertSame($applicant, $this->licenseeHelper->getLicenseeFromSession());
    }

    public function testApiRequestWithTheIdOfAnotherAccountsLicenseeIsForbidden(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(Request::create('/api/v1/me', server: ['HTTP_X_LICENSEE' => (string) self::FOREIGN_ID]));
        $this->user([$this->licensee(self::LICENSEE_ID)]);

        $this->expectException(AccessDeniedHttpException::class);
        $this->licenseeHelper->getLicenseeFromSession();
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

    private function licensee(int $id): Licensee
    {
        $licensee = new Licensee();
        $this->setId($licensee, $id);

        return $licensee;
    }

    /**
     * A real User (getLicenseeWithId() is the code under test), logged in.
     *
     * @param list<Licensee> $licensees
     */
    private function user(array $licensees): User
    {
        $user = new User();
        foreach ($licensees as $licensee) {
            $user->addLicensee($licensee);
        }

        $this->security->method('getUser')->willReturn($user);

        return $user;
    }

    private function setId(Licensee $licensee, int $id): void
    {
        new \ReflectionProperty(Licensee::class, 'id')->setValue($licensee, $id);
    }
}
