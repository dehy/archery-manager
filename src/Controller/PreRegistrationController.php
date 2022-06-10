<?php

namespace App\Controller;

use App\Entity\Applicant;
use App\Form\ApplicantType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class PreRegistrationController extends AbstractController
{
    #[Route("/pre-inscription", name: "app_pre_registration")]
    public function form(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $applicant = new Applicant();

        $form = $this->createForm(ApplicantType::class, $applicant);
        $form->add("submit", SubmitType::class, [
            "label" => "Enregistrer ma pré-inscription",
            "attr" => [
                "class" => "btn btn-primary mb-3",
            ],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isSubmitted()) {
            $applicant->setRegisteredAt(new DateTimeImmutable());
            $entityManager->persist($applicant);
            $entityManager->flush();

            $email = (new TemplatedEmail())
                ->to($applicant->getEmail())
                ->replyTo("lesarchersdeguyenne@gmail.com")
                ->subject("Votre pré-inscription aux Archers de Guyenne")
                ->htmlTemplate("pre_registration/mail_confirmation.html.twig");

            $mailer->send($email);

            return $this->redirectToRoute("app_pre_registration_thank_you");
        }

        return $this->render("pre_registration/form.html.twig", [
            "form" => $form->createView(),
        ]);
    }

    #[Route("/pre-inscription/merci", name: "app_pre_registration_thank_you")]
    public function thankYou(): Response
    {
        return $this->render("pre_registration/thank_you.html.twig");
    }
}
