<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\PracticeAdvice;
use App\Helper\LicenseeHelper;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class PracticeAdviceCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly LicenseeHelper $licenseeHelper,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public static function getEntityFqcn(): string
    {
        return PracticeAdvice::class;
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('licensee'),
            TextField::new('title'),
            TextEditorField::new('advice'),
            AssociationField::new('author'),
            DateTimeField::new('createdAt')->hideOnForm(),
            BooleanField::new('archivedAt', 'Archived')
                ->hideOnForm()
                ->renderAsSwitch(false),
        ];
    }

    #[\Override]
    public function createEntity(string $entityFqcn): PracticeAdvice
    {
        /** @var PracticeAdvice $advice */
        $advice = new $entityFqcn();
        $advice->setAuthor($this->licenseeHelper->getLicenseeFromSession());

        return $advice;
    }

    #[\Override]
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var PracticeAdvice $advice */
        $advice = $entityInstance;
        $entityManager->persist($advice);
        $entityManager->flush();

        $mail = $this->generateNotificationMail($advice, 'practice_advice/mail_notification_new.html.twig');

        try {
            $this->mailer->send($mail);
        } catch (TransportExceptionInterface $transportException) {
            $this->logger->error($transportException->getMessage());
        }
    }

    #[\Override]
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var PracticeAdvice $advice */
        $advice = $entityInstance;
        $entityManager->flush();

        $mail = $this->generateNotificationMail($advice, 'practice_advice/mail_notification_update.html.twig');

        try {
            $this->mailer->send($mail);
        } catch (TransportExceptionInterface $transportException) {
            $this->logger->error($transportException->getMessage());
        }
    }

    private function generateNotificationMail(PracticeAdvice $advice, string $template): TemplatedEmail
    {
        $mail = new TemplatedEmail();
        $mail->to($advice->getLicensee()->getUser()->getEmail());
        $mail->subject(\sprintf('[Conseil] %s', $advice->getTitle()));
        $mail->htmlTemplate($template);
        $mail->context(['advice' => $advice]);

        return $mail;
    }
}
