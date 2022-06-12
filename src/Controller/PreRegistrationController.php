<?php

namespace App\Controller;

use App\Entity\Applicant;
use App\Form\ApplicantRenewalType;
use App\Form\ApplicantType;
use App\Repository\ApplicantRepository;
use App\Repository\LicenseeRepository;
use DateTimeImmutable;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class PreRegistrationController extends AbstractController
{
    #[Route("/pre-inscription", name: "app_pre_registration")]
    public function form(
        Request $request,
        ApplicantRepository $applicantRepository,
        LicenseeRepository $licenseeRepository,
        MailerInterface $mailer
    ): Response {
        $applicant = new Applicant();
        $error = null;

        $form = $this->createForm(ApplicantType::class, $applicant);
        $form->add("submit", SubmitType::class, [
            "label" => "Enregistrer ma pré-inscription",
            "attr" => [
                "class" => "btn btn-primary mb-3",
            ],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isSubmitted()) {
            $licensee = $applicant->getLicenseNumber()
                ? $licenseeRepository->findByCode(
                    $applicant->getLicenseNumber()
                )
                : null;

            if (!$licensee) {
                $applicant->setRegisteredAt(new DateTimeImmutable());
                $applicantRepository->add($applicant);

                $email = (new TemplatedEmail())
                    ->to($applicant->getEmail())
                    ->replyTo("lesarchersdeguyenne@gmail.com")
                    ->subject("Votre pré-inscription aux Archers de Guyenne")
                    ->htmlTemplate(
                        "pre_registration/mail_confirmation.html.twig"
                    );

                $mailer->send($email);

                $notificationEmail = (new TemplatedEmail())
                    ->to("lesarchersdeguyenne@gmail.com")
                    ->subject("Nouvelle pré-inscription")
                    ->textTemplate(
                        "pre_registration/mail_notification.txt.twig"
                    )
                    ->context(["applicant" => $applicant]);

                $mailer->send($notificationEmail);

                return $this->redirectToRoute("app_pre_registration_thank_you");
            }
            $error = "existing_licensee";
        }

        return $this->render("pre_registration/form.html.twig", [
            "form" => $form->createView(),
            "error" => $error,
        ]);
    }

    #[Route("/pre-inscription/merci", name: "app_pre_registration_thank_you")]
    public function thankYou(): Response
    {
        return $this->render("pre_registration/thank_you.html.twig");
    }

    #[
        Route(
            "/pre-inscription-renouvellement",
            name: "app_registration_renewal"
        )
    ]
    public function renewal(
        Request $request,
        ApplicantRepository $applicantRepository,
        LicenseeRepository $licenseeRepository,
        MailerInterface $mailer
    ): Response {
        $applicant = new Applicant();
        $error = null;

        $form = $this->createForm(ApplicantRenewalType::class, $applicant);
        $form->add("submit", SubmitType::class, [
            "label" => "Enregistrer ma pré-inscription",
            "attr" => [
                "class" => "btn btn-primary mb-3",
            ],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $licensee = $applicant->getLicenseNumber()
                ? $licenseeRepository->findByCode(
                    $applicant->getLicenseNumber()
                )
                : null;

            if ($licensee) {
                $applicant->setBirthdate(
                    DateTimeImmutable::createFromMutable(
                        $licensee->getBirthdate()
                    )
                );
                $applicant->setEmail($licensee->getUser()->getEmail());
                $applicant->setPhoneNumber(
                    $licensee->getUser()->getPhoneNumber()
                );
                $applicant->setRenewal(true);
                $applicant->setRegisteredAt(new DateTimeImmutable());

                $applicantRepository->add($applicant);

                $email = (new TemplatedEmail())
                    ->to($applicant->getEmail())
                    ->replyTo("lesarchersdeguyenne@gmail.com")
                    ->subject("Votre renouvellement aux Archers de Guyenne")
                    ->htmlTemplate(
                        "pre_registration/mail_renewal_confirmation.html.twig"
                    );

                $mailer->send($email);

                $notificationEmail = (new TemplatedEmail())
                    ->to("lesarchersdeguyenne@gmail.com")
                    ->subject("Nouveau renouvellement")
                    ->textTemplate(
                        "pre_registration/mail_renewal_notification.txt.twig"
                    )
                    ->context(["applicant" => $applicant]);

                $mailer->send($notificationEmail);

                return $this->redirectToRoute(
                    "app_registration_renewal_thank_you"
                );
            }
            $error = "unknown_licensee";
        }

        return $this->render("pre_registration/renewal.html.twig", [
            "form" => $form->createView(),
            "error" => $error,
        ]);
    }

    #[
        Route(
            "/pre-inscription-renouvellement/merci",
            name: "app_registration_renewal_thank_you"
        )
    ]
    public function renewalThankYou(): Response
    {
        return $this->render("pre_registration/renewal_thank_you.html.twig");
    }
}
