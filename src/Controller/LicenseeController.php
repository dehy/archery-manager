<?php

namespace App\Controller;

use App\Entity\Licensee;
use App\Entity\User;
use App\Repository\LicenseeRepository;
use Doctrine\ORM\NonUniqueResultException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class LicenseeController extends AbstractController
{
    #[Route("/my-profile", name: "app_licensee_my_profile", methods: ["GET"])]
    #[
        Route(
            "/licensee/{fftaCode}",
            name: "app_licensee_profile",
            methods: ["GET"]
        )
    ]
    public function profile(
        Request $request,
        LicenseeRepository $licenseeRepository,
        ?string $fftaCode
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($fftaCode) {
            $licensee = $licenseeRepository->findByCode($fftaCode);
        } else {
            $licensee = $user->getLicensees()->first();
        }

        if (
            !$licensee ||
            ($user->getLicensees()->contains($licensee) &&
                !$this->isGranted("ROLE_ADMIN"))
        ) {
            throw new NotFoundHttpException();
        }
        return $this->render("licensee/show.html.twig", [
            "licensee" => $licensee,
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[
        Route(
            "/licensee/{fftaCode}/picture",
            name: "app_licensee_picture",
            methods: ["GET"]
        )
    ]
    public function profilePicture(
        string $fftaCode,
        LicenseeRepository $licenseeRepository,
        FilesystemOperator $profilePicturesStorage
    ): Response {
        $licensee = $licenseeRepository->findByCode($fftaCode);
        if (!$licensee) {
            throw new NotFoundHttpException();
        }

        $imagePath = sprintf("%s.jpg", $fftaCode);
        try {
            $profilePicture = $profilePicturesStorage->read($imagePath);
            $contentType = "image/jpeg";
        } catch (FilesystemException) {
            $profilePicture = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<svg width="100%" height="100%" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg" xml:space="preserve" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
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
}
