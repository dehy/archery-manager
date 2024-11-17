<?php

declare(strict_types=1);

namespace App\Tests\Integration\EventListener;

use App\Entity\Licensee;
use App\Entity\User;
use App\EventListener\SwitchLicenseeListener;
use App\Helper\LicenseeHelper;
use App\Tests\SecurityTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Mailer\MailerInterface;

final class SwitchLicenseeListenerTest extends KernelTestCase
{
    use SecurityTrait;

    private EventDispatcher $dispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    /**
     * @throws \Exception
     */
    public function testShouldSwitchLicensee(): void
    {
        $session = new Session(new MockArraySessionStorage());
        /** @var Security $security */
        $security = self::getContainer()->get(Security::class);
        /** @var MailerInterface $mailer */
        $mailer = self::getContainer()->get(MailerInterface::class);

        $request = new Request();
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $licenseeHelper = new LicenseeHelper($requestStack, $security, $mailer);

        $user = new User();
        $licensee = (new Licensee())
            ->setFftaMemberCode('1234567A')
            ->setUser($user);

        $licensee2 = (new Licensee())
            ->setFftaMemberCode('1234567B')
            ->setUser($user);

        $this->login($user);

        $this->assertEquals($user, $security->getUser());
        $this->assertEquals($licensee, $licenseeHelper->getLicenseeFromSession());

        // instance our own listener
        $listener = new SwitchLicenseeListener($security, $licenseeHelper);
        $this->dispatcher->addListener('onKernelRequest', $listener->onKernelRequest(...));

        $query_params = [
            '_switch_licensee' => $licensee2->getFftaMemberCode(),
        ];
        $request = new Request($query_params);

        // dispatch your event here
        $event = new RequestEvent(self::$kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $this->dispatcher->dispatch($event, 'onKernelRequest');

        $this->assertEquals($licensee2, $licenseeHelper->getLicenseeFromSession());

        $this->logout();
    }
}
