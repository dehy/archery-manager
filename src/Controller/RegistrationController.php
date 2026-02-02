<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private readonly EmailVerifier $emailVerifier, private readonly UserPasswordHasherInterface $userPasswordHasher, private readonly TranslatorInterface $translator, private readonly UserRepository $userRepository)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData(),
                ),
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                new TemplatedEmail()
                    ->from(
                        new Address(
                            'archerie@admds.net',
                            "L'Archerie des Archers de Guyenne",
                        ),
                    )
                    ->to($user->getEmail())
                    ->subject('Merci de confirmer ton Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig'),
            );
            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
    ): Response {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $this->userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $verifyEmailException) {
            $this->addFlash(
                'verify_email_error',
                $this->translator->trans(
                    $verifyEmailException->getReason(),
                    [],
                    'VerifyEmailBundle',
                ),
            );

            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Ton email a été vérifié.');

        return $this->redirectToRoute('app_register');
    }
}
