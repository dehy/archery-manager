<?php

declare(strict_types=1);

namespace App\Controller;

use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\LicenseActivityType;
use App\DBAL\Types\UserRoleType;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use App\Entity\Result;
use App\Entity\Season;
use App\Entity\User;
use App\Helper\ClubHelper;
use App\Helper\FftaHelper;
use App\Helper\LicenseHelper;
use App\Helper\ResultHelper;
use App\Repository\LicenseeRepository;
use App\Repository\ResultRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class LicenseeController extends BaseController
{
    #[Route('/licensees', name: 'app_licensee_index')]
    public function index(
        LicenseeRepository $licenseeRepository,
        LicenseHelper $licenseHelper,
    ): Response {
        $this->assertHasValidLicense();

        $season = $this->seasonHelper->getSelectedSeason();
        $club = $licenseHelper->getCurrentLicenseeCurrentLicense()->getClub();
        $licensees = new ArrayCollection(
            $licenseeRepository->findByLicenseYear($club, $season),
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
            'year' => $season,
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
        ResultRepository $resultRepository,
        ChartBuilderInterface $chartBuilder,
        ?string $fftaCode,
    ): Response {
        $this->assertHasValidLicense();

        /** @var User $user */
        $user = $this->getUser();

        $licensee = null !== $fftaCode && '' !== $fftaCode && '0' !== $fftaCode ? $licenseeRepository->findOneByCode($fftaCode) : $this->licenseeHelper->getLicenseeFromSession();

        if (
            !$licensee instanceof Licensee
            || (!$user->getLicensees()->contains($licensee)
                && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_COACH'))
        ) {
            throw $this->createNotFoundException();
        }

        $resultsBySeason = [];
        $seasons = [];
        /** @var Result[] $licenseeResults */
        $licenseeResults = $resultRepository->findForLicensee(
            $licensee
        );
        foreach ($licenseeResults as $result) {
            $season = Season::seasonForDate($result->getEvent()->getStartsAt());
            $seasons[\sprintf('Saison %s', $season)] = $season;
            $groupName = \sprintf(
                '%s %s %sm',
                DisciplineType::getReadableValue($result->getDiscipline()),
                LicenseActivityType::getReadableValue($result->getActivity()),
                $result->getDistance()
            );
            $resultsBySeason[$season][$groupName]['max'] = $result->getMaxTotal();
            $resultsBySeason[$season][$groupName]['results'][] = $result;
        }

        krsort($seasons);

        $resultsCharts = [];

        foreach ($resultsBySeason as $season => $resultsByCategory) {
            foreach ($resultsByCategory as $category => $categoryResults) {
                $results = $categoryResults['results'];
                $resultsTotals = array_map(fn (Result $result): ?int => $result->getTotal(), $results);

                $lowestScore = min($resultsTotals);
                $bestScore = max($resultsTotals);
                $highest3Scores = $resultsTotals;
                sort($highest3Scores);
                $highest3Scores = \array_slice($highest3Scores, -3, 3);
                $averageScore = floor(array_sum($highest3Scores) / \count($highest3Scores));

                $scoreDiff = $bestScore - $lowestScore;

                $resultsChart = $chartBuilder->createChart(Chart::TYPE_BAR);
                $resultsChart->setData([
                    'labels' => array_map(fn (Result $result): ?string => $result->getEvent()->getName(), $results),
                    'datasets' => [
                        [
                            'label' => 'Score Total',
                            'data' => array_map(fn (Result $result): ?int => $result->getTotal(), $results),
                            'backgroundColor' => array_map(
                                function (Result $result) use ($lowestScore, $scoreDiff): string {
                                    if (0 === $scoreDiff) {
                                        return ResultHelper::colorRatio(1);
                                    }

                                    return ResultHelper::colorRatio(
                                        ($result->getTotal() - $lowestScore) / $scoreDiff
                                    );
                                },
                                $results
                            ),
                            'datalabels' => [
                                'color' => 'white',
                                'font' => [
                                    'weight' => 'bold',
                                ],
                                'align' => 'end',
                            ],
                        ],
                    ],
                ]);

                $resultsChart->setOptions([
                    'aspectRatio' => 5 / 3,
                    'scales' => [
                        'y' => [
                            'min' => floor($lowestScore * 0.98),
                            'max' => floor($bestScore * 1.02),
                        ],
                    ],
                    'plugins' => [
                        'legend' => [
                            'display' => false,
                        ],
                        'annotation' => [
                            'annotations' => [
                                'lineBest' => [
                                    'type' => 'line',
                                    'yMin' => $bestScore,
                                    'yMax' => $bestScore,
                                    'borderColor' => 'rgba(227, 29, 2, 0.8)',
                                    'borderWidth' => 2,
                                    'label' => [
                                        'display' => true,
                                        'backgroundColor' => 'rgba(227, 29, 2, 0.6)',
                                        'borderRadius' => 7,
                                        'color' => 'white',
                                        'font' => [
                                            'weight' => 'bold',
                                        ],
                                        'content' => \sprintf('Meilleur : %s', $bestScore),
                                        'xAdjust' => -100,
                                    ],
                                ],
                                'lineAverage' => [
                                    'type' => 'line',
                                    'yMin' => $averageScore,
                                    'yMax' => $averageScore,
                                    'borderColor' => 'rgba(18, 95, 155, 0.8)',
                                    'borderWidth' => 1,
                                    'borderDash' => [15, 10],
                                    'label' => [
                                        'display' => true,
                                        'backgroundColor' => 'rgba(18, 95, 155, 0.6)',
                                        'borderRadius' => 7,
                                        'color' => 'white',
                                        'font' => [
                                            'weight' => 'bold',
                                        ],
                                        'content' => \sprintf('Moyenne : %s', $averageScore),
                                        'xAdjust' => 0,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

                $resultsCharts[$season][$category] = $resultsChart;
            }
        }

        ksort($resultsCharts);

        $licenseeSyncForm = null;
        if ($this->isGranted('ROLE_ADMIN')) {
            $licenseeSyncForm = $this->createSyncForm($licensee);
        }

        return $this->render('licensee/show.html.twig', [
            'licensee' => $licensee,
            'seasons' => $seasons,
            'results_charts' => $resultsCharts,
            'licensee_sync_form' => $licenseeSyncForm?->createView(),
        ]);
    }

    /**
     * @throws NonUniqueResultException
     * @throws TransportExceptionInterface
     */
    #[Route('/licensee/{fftaCode}/sync', methods: ['POST'])]
    public function sync(
        string $fftaCode,
        LicenseeRepository $licenseeRepository,
        FftaHelper $fftaHelper,
        ClubHelper $clubHelper,
        Request $request,
    ): RedirectResponse {
        $this->assertHasValidLicense();
        $this->isGranted(UserRoleType::ADMIN);

        $licensee = $licenseeRepository->findOneByCode($fftaCode);
        if (!$licensee instanceof Licensee) {
            throw $this->createNotFoundException();
        }

        $form = $this->createSyncForm($licensee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $fftaHelper->syncLicenseeWithId(
                    $clubHelper->activeClub(),
                    $licensee->getFftaId(),
                    $this->seasonHelper->getSelectedSeason(),
                );
                $this->addFlash(
                    'success',
                    \sprintf('Le profil de %s a été synchronisé avec succès !', $licensee->getFirstname())
                );
            } catch (\Exception $e) {
                if ($this->getParameter('kernel.debug')) {
                    throw $e;
                }

                $this->addFlash(
                    'danger',
                    \sprintf('Une erreur est survenue durant la synchronisation: %s', $e->getMessage())
                );
            }
        }

        return $this->redirectToRoute('app_licensee_profile', ['fftaCode' => $fftaCode]);
    }

    private function createSyncForm(Licensee $licensee): FormInterface
    {
        return $this->createFormBuilder(null, [
            'action' => $this->generateUrl('app_licensee_sync', ['fftaCode' => $licensee->getFftaMemberCode()]),
            'method' => 'POST',
        ])->getForm();
    }

    /**
     * @throws NonUniqueResultException|FilesystemException
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
        if (!$licensee instanceof Licensee) {
            throw $this->createNotFoundException();
        }

        $response = new Response();
        $response->setLastModified($licensee->getUpdatedAt());

        if ($response->isNotModified($request)) {
            return $response;
        }

        $imagePath = \sprintf('%s.jpg', $fftaCode);

        if (!$licenseesStorage->fileExists($imagePath)) {
            return new Response(
                '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
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
</svg>',
                Response::HTTP_OK,
                [
                    'Content-Type' => 'image/svg+xml',
                ]
            );
        }

        $response = new StreamedResponse(function () use ($licenseesStorage, $imagePath): void {
            $outputStream = fopen('php://output', 'w');
            $fileStream = $licenseesStorage->readStream($imagePath);

            stream_copy_to_stream($fileStream, $outputStream);
        }, Response::HTTP_OK, [
            'Content-Disposition' => ResponseHeaderBag::DISPOSITION_INLINE,
        ]);
        $response->setLastModified($licensee->getUpdatedAt());

        return $response;
    }

    #[Route('/licensees/attachments/{attachment}', name: 'licensees_attachements_download')]
    public function downloadAttachement(
        Request $request,
        LicenseeAttachment $attachment,
        FilesystemOperator $licenseesStorage
    ): Response {
        $forceDownload = $request->query->get('forceDownload');
        $contentDisposition = \sprintf(
            '%s; filename="%s"',
            $forceDownload ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $attachment->getFile()->getName()
        );

        $response = new StreamedResponse(function () use ($licenseesStorage, $attachment): void {
            $outputStream = fopen('php://output', 'w');
            $fileStream = $licenseesStorage->readStream($attachment->getFile()->getName());

            stream_copy_to_stream($fileStream, $outputStream);
        }, Response::HTTP_OK, [
            'Content-Type' => $attachment->getFile()->getMimeType(),
            'Content-Disposition' => $contentDisposition,
            'Content-Length' => $attachment->getFile()->getSize(),
        ]);
        $response->setLastModified($attachment->getUpdatedAt());

        return $response;
    }
}
