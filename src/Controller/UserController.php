<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Helper\StringHelper;
use App\Repository\UserRepository;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/user")]
class UserController extends AbstractController
{
    #[Route("/my-profile", name: "app_user_profile", methods: ["GET"])]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        return $this->render("user/show.html.twig", [
            "user" => $user,
        ]);
    }

    #[Route("/", name: "app_user_index", methods: ["GET"])]
    public function index(UserRepository $userRepository): Response {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        return $this->render("user/index.html.twig", [
            "users" => $userRepository->findAll(),
        ]);
    }

    #[Route("/new", name: "app_user_new", methods: ["GET", "POST"])]
    public function new(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $randomPassword = StringHelper::randomPassword(12);
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $randomPassword
            );
            $user->setPassword($hashedPassword);

            $userRepository->add($user);
            return $this->redirectToRoute(
                "app_user_show",
                ["id" => $user->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm("user/new.html.twig", [
            "user" => $user,
            "form" => $form,
        ]);
    }

    #[Route("/{id}", name: "app_user_show", methods: ["GET"])]
    public function show(User $user): Response {
        if (
            $user->getUserIdentifier() !== $this->getUser()->getUserIdentifier()
        ) {
            $this->denyAccessUnlessGranted("ROLE_ADMIN");
        }
        return $this->render("user/show.html.twig", [
            "user" => $user,
        ]);
    }

    #[Route("/{id}/picture", name: "app_user_picture", methods: ["GET"])]
    public function profilePicture(
        User $user,
        FilesystemOperator $profilePicturesStorage
    ): Response {
        $imagePath = sprintf("%s.jpg", sha1($user->getUserIdentifier()));
        try {
            $profilePicture = $profilePicturesStorage->read($imagePath);
            $contentType = "image/jpeg";
        } catch (FilesystemException $exception) {
            $profilePicture = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<svg width="100%" height="100%" viewBox="0 0 300 300" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
    <g transform="matrix(0.985098,0,0,0.985098,1.63499,1.93637)">
        <g id="Calque1">
            <rect x="-1.66" y="-2.023" width="304.538" height="304.653" style="fill:rgb(226,226,226);"/>
        </g>
    </g>
    <g transform="matrix(0.401786,0,0,0.401786,150,150)">
        <g transform="matrix(1,0,0,1,-224,-256)">
            <path d="M224,256C294.7,256 352,198.69 352,128C352,57.31 294.7,0 224,0C153.3,0 96,57.31 96,128C96,198.69 153.3,256 224,256ZM274.7,304L173.3,304C77.61,304 0,381.6 0,477.3C0,496.44 15.52,511.97 34.66,511.97L413.36,511.97C432.5,512 448,496.5 448,477.3C448,381.6 370.4,304 274.7,304Z" style="fill-rule:nonzero;"/>
        </g>
    </g>
</svg>
';
            $contentType = "image/svg+xml";
        }

        return new Response($profilePicture, 200, [
            "Content-Type" => $contentType,
        ]);
    }

    #[Route("/{id}/edit", name: "app_user_edit", methods: ["GET", "POST"])]
    public function edit(
        Request $request,
        User $user,
        UserRepository $userRepository
    ): Response {
        if (
            $user->getUserIdentifier() !== $this->getUser()->getUserIdentifier()
        ) {
            $this->denyAccessUnlessGranted("ROLE_ADMIN");
        }
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository->add($user);
            return $this->redirectToRoute(
                "app_user_index",
                [],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm("user/edit.html.twig", [
            "user" => $user,
            "form" => $form,
        ]);
    }

    #[Route("/{id}", name: "app_user_delete", methods: ["POST"])]
    public function delete(
        Request $request,
        User $user,
        UserRepository $userRepository
    ): Response {
        if (
            $user->getUserIdentifier() !== $this->getUser()->getUserIdentifier()
        ) {
            $this->denyAccessUnlessGranted("ROLE_ADMIN");
        }
        if (
            $this->isCsrfTokenValid(
                "delete" . $user->getId(),
                $request->request->get("_token")
            )
        ) {
            $userRepository->remove($user);
        }

        return $this->redirectToRoute(
            "app_user_index",
            [],
            Response::HTTP_SEE_OTHER
        );
    }
}
