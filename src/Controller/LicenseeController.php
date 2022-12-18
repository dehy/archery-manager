<?php

namespace App\Controller;

use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use App\Entity\User;
use App\Helper\LicenseeHelper;
use App\Repository\LicenseeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class LicenseeController extends AbstractController
{
    #[Route('/licensees', name: 'app_licensee_index')]
    public function index(LicenseeRepository $licenseeRepository): Response
    {
        $year = 2023;
        $licensees = new ArrayCollection(
            $licenseeRepository->findByLicenseYear($year),
        );

        /** @var ArrayCollection<int, Licensee> $orderedLicensees */
        $orderedLicensees = $licensees->matching(
            Criteria::create()->orderBy([
                'firstname' => 'ASC',
                'lastname' => 'ASC',
            ]),
        );

        return $this->render('licensee/index.html.twig', [
            'licensees' => $orderedLicensees,
            'year' => $year,
        ]);
    }

    #[Route('/my-profile', name: 'app_licensee_my_profile', methods: ['GET'])]
    #[
        Route(
            '/licensee/{fftaCode}',
            name: 'app_licensee_profile',
            methods: ['GET'],
        ),
    ]
    public function show(
        LicenseeRepository $licenseeRepository,
        LicenseeHelper $licenseeHelper,
        ?string $fftaCode,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($fftaCode) {
            $licensee = $licenseeRepository->findOneByCode($fftaCode);
        } else {
            $licensee = $licenseeHelper->getLicenseeFromSession();
        }

        if (
            !$licensee
            || (!$user->getLicensees()->contains($licensee)
                && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_COACH'))
        ) {
            throw $this->createNotFoundException();
        }

        return $this->render('licensee/show.html.twig', [
            'licensee' => $licensee,
        ]);
    }

    /**
     * @throws NonUniqueResultException
     */
    #[
        Route(
            '/licensee/{fftaCode}/picture',
            name: 'app_licensee_picture',
            methods: ['GET'],
        ),
    ]
    public function profilePicture(
        string $fftaCode,
        LicenseeRepository $licenseeRepository,
        FilesystemOperator $licenseesStorage,
        Request $request,
    ): Response {
        $licensee = $licenseeRepository->findOneByCode($fftaCode);
        if (!$licensee) {
            throw $this->createNotFoundException();
        }

        $response = new Response();
        $response->setLastModified($licensee->getUpdatedAt());

        if ($response->isNotModified($request)) {
            return $response;
        }

        $imagePath = sprintf('%s.jpg', $fftaCode);

        try {
            $profilePicture = $licenseesStorage->read($imagePath);
            $contentType = 'image/jpeg';
        } catch (FilesystemException) {
            $profilePicture = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<svg width="100%" height="100%" viewBox="0 0 175 275"
     xmlns="http://www.w3.org/2000/svg" xml:space="preserve"
     style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
    <g transform="matrix(0.57464,0,0,0.919078,0.953744,-0.640704)">
        <g id="Calque1">
            <rect x="-1.66" y="-2.023" width="304.538" height="304.653" style="fill:rgb(226,226,226);"/>
        </g>
    </g>
    <g transform="matrix(0.234375,0,0,0.234375,35,77.5)">
        <g>
            <path d="M224,256C294.7,256 352,198.69 352,128C352,57.31 294.7,0 224,0C153.3,0 96,57.31
                     96,128C96,198.69 153.3,256 224,256ZM274.7,304L173.3,304C77.61,304 0,381.6 0,477.3C0,496.44
                     15.52,511.97 34.66,511.97L413.36,511.97C432.5,512 448,496.5 448,477.3C448,381.6 370.4,304 274.7,304Z"
                  style="fill-rule:nonzero;"/>
        </g>
    </g>
</svg>
';
            $contentType = 'image/svg+xml';
        }

        $response->headers->set('Content-Type', $contentType);
        $response->setContent($profilePicture);
        $response->setStatusCode(200);

        return $response;
    }

    #[Route('/licensees/attachments/{attachment}', name: 'licensees_attachements_download')]
    public function downloadAttachement(Request $request, LicenseeAttachment $attachment, FilesystemOperator $licenseesStorage): Response
    {
        $forceDownload = $request->query->get('forceDownload');
        $contentDisposition = sprintf(
            '%s; filename="%s"',
            $forceDownload ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $attachment->getFile()->getName()
        );

        $response = new StreamedResponse(function () use ($licenseesStorage, $attachment) {
            $outputStream = fopen('php://output', 'wb');
            $fileStream = $licenseesStorage->readStream($attachment->getFile()->getName());

            stream_copy_to_stream($fileStream, $outputStream);
            }, 200, [
                'Content-Type' => $attachment->getFile()->getMimeType(),
                'Content-Disposition' => $contentDisposition,
                'Content-Length' => $attachment->getFile()->getSize(),
            ]);
        $response->setLastModified($attachment->getUpdatedAt());

        return $response;
    }
}
