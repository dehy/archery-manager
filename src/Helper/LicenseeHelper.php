<?php

namespace App\Helper;

use App\Entity\Licensee;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class LicenseeHelper
{
    final public const SESSION_KEY = 'selectedLicensee';

    protected SessionInterface $session;

    public function __construct(
        protected RequestStack $requestStack,
        protected Security $security,
        protected MailerInterface $mailer,
    ) {
    }

    public function getLicenseeFromSession(): Licensee
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $licenseeCode = $this->requestStack
            ->getSession()
            ->get(self::SESSION_KEY);
        if (null !== $licenseeCode && !$user->getLicensees()->containsKey($licenseeCode)) {
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


    /**
     * @throws TransportExceptionInterface
     */
    public function sendWelcomeEmail(Licensee $licensee): void
    {
        $email = (new TemplatedEmail())
            ->to($licensee->getUser()->getEmail())
            ->replyTo('lesarchersdeguyenne@gmail.com')
            ->subject('Bienvenue aux Archers de Guyenne')
            ->htmlTemplate(
                'licensee/mail_account_created.html.twig',
            )
            ->context([
                'licensee' => $licensee,
            ]);

        $this->mailer->send($email);
    }
}
