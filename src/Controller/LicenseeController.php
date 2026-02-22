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
use App\Form\Type\LicenseeFormType;
use App\Helper\ClubHelper;
use App\Helper\FftaHelper;
use App\Helper\LicenseHelper;
use App\Helper\ResultHelper;
use App\Repository\EquipmentLoanRepository;
use App\Repository\GroupRepository;
use App\Repository\LicenseeRepository;
use App\Repository\ResultRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class LicenseeController extends BaseController
{
    public function __construct(\App\Helper\LicenseeHelper $licenseeHelper, \App\Helper\SeasonHelper $seasonHelper, private readonly LicenseeRepository $licenseeRepository, private readonly LicenseHelper $licenseHelper, private readonly GroupRepository $groupRepository, private readonly ResultRepository $resultRepository, private readonly EquipmentLoanRepository $loanRepository, private readonly ChartBuilderInterface $chartBuilder, private readonly FftaHelper $fftaHelper, private readonly ClubHelper $clubHelper, private readonly FilesystemOperator $licenseesStorage)
    {
        parent::__construct($licenseeHelper, $seasonHelper);
    }

    #[Route('/licensees', name: 'app_licensee_index')]
    public function index(
        Request $request,
    ): Response {
        $this->assertHasValidLicense();

        $season = $this->seasonHelper->getSelectedSeason();
        $club = $this->licenseHelper->getCurrentLicenseeCurrentLicense()->getClub();

        // Récupérer le filtre de groupe depuis la query string
        $groupId = $request->query->get('group');
        $selectedGroup = null;

        if ($groupId) {
            $selectedGroup = $this->groupRepository->find($groupId);
        }

        $licensees = new ArrayCollection(
            $this->licenseeRepository->findByLicenseYear($club, $season),
        );

        // Compter le nombre total de licenciés avant filtrage
        $totalLicensees = $licensees->count();

        // Compter les licenciés sans groupe avant filtrage
        $noGroupLicenseesCount = $licensees->filter(static fn ($licensee) => $licensee->getGroups()->isEmpty())->count();

        // Filtrer par groupe si un groupe est sélectionné
        if ($selectedGroup) {
            $licensees = $licensees->filter(static fn ($licensee) => $licensee->getGroups()->contains($selectedGroup));
        } elseif ('no-group' === $groupId) {
            // Filtrer les licenciés sans groupe
            $licensees = $licensees->filter(static fn ($licensee) => $licensee->getGroups()->isEmpty());
        }

        /** @var ArrayCollection<int, Licensee> $orderedLicensees */
        $orderedLicensees = $licensees->matching(
            Criteria::create()->orderBy([
                'firstname' => 'ASC',
                'lastname' => 'ASC',
            ]),
        );

        // Récupérer tous les groupes pour l'affichage des filtres
        $allGroups = $this->groupRepository->findBy(['club' => $club], ['name' => 'ASC']);

        return $this->render('licensee/index.html.twig', [
            'licensees' => $orderedLicensees,
            'year' => $season,
            'selectedGroup' => $selectedGroup,
            'allGroups' => $allGroups,
            'totalLicensees' => $totalLicensees,
            'noGroupLicenseesCount' => $noGroupLicenseesCount,
            'isNoGroupFilter' => 'no-group' === $groupId,
        ]);
    }

    #[Route('/my-profile', name: 'app_licensee_my_profile', methods: ['GET'])]
    #[
        Route('/licensee/{id}', name: 'app_licensee_profile', requirements: ['id' => '\d+'], methods: ['GET']),
    ]
    public function show(
        ?int $id = null,
    ): Response {
        $this->assertHasValidLicense();

        /** @var User $user */
        $user = $this->getUser();

        $licensee = null !== $id ? $this->licenseeRepository->find($id) : $this->licenseeHelper->getLicenseeFromSession();

        if (!$licensee instanceof Licensee) {
            throw $this->createNotFoundException();
        }

        $this->checkLicenseeAccess($user, $licensee);

        [$seasons, $resultsCharts] = $this->buildResultsData($licensee);

        // Fetch active equipment loans for this licensee
        $activeLoans = $this->loanRepository->findActiveLoansByBorrower($licensee);

        $licenseeSyncForm = null;
        if ($this->isGranted('ROLE_ADMIN')) {
            $licenseeSyncForm = $this->createSyncForm($licensee);
        }

        return $this->render('licensee/show.html.twig', [
            'licensee' => $licensee,
            'seasons' => $seasons,
            'results_charts' => $resultsCharts,
            'licensee_sync_form' => $licenseeSyncForm?->createView(),
            'activeLoans' => $activeLoans,
        ]);
    }

    /**
     * @throws NonUniqueResultException
     * @throws TransportExceptionInterface
     */
    #[Route('/licensee/{id}/sync', name: 'app_licensee_sync', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function sync(
        int $id,
        Request $request,
    ): RedirectResponse {
        $this->assertHasValidLicense();
        $this->isGranted(UserRoleType::ADMIN);

        $licensee = $this->licenseeRepository->find($id);
        if (!$licensee instanceof Licensee) {
            throw $this->createNotFoundException();
        }

        $form = $this->createSyncForm($licensee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->fftaHelper->syncLicenseeWithId(
                    $this->clubHelper->activeClub(),
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

        return $this->redirectToRoute('app_licensee_profile', ['id' => $licensee->getId()]);
    }

    private function createSyncForm(Licensee $licensee): FormInterface
    {
        return $this->createFormBuilder(null, [
            'action' => $this->generateUrl('app_licensee_sync', ['id' => $licensee->getId()]),
            'method' => 'POST',
        ])->getForm();
    }

    /**
     * @throws NonUniqueResultException|FilesystemException
     */
    #[Route('/licensee/{id}/picture', name: 'app_licensee_picture', requirements: ['id' => '\d+'], methods: ['GET']),]
    public function profilePicture(
        int $id,
        Request $request,
    ): Response {
        $licensee = $this->licenseeRepository->find($id);
        if (!$licensee instanceof Licensee) {
            throw $this->createNotFoundException();
        }

        $response = new Response();
        $response->setLastModified($licensee->getUpdatedAt());

        if ($response->isNotModified($request)) {
            return $response;
        }

        $imagePath = \sprintf('%s.jpg', $licensee->getFftaMemberCode());

        if (!$this->licenseesStorage->fileExists($imagePath)) {
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

        $response = new StreamedResponse(function () use ($imagePath): void {
            $outputStream = fopen('php://output', 'w');
            $fileStream = $this->licenseesStorage->readStream($imagePath);

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
        LicenseeAttachment $attachment
    ): Response {
        $forceDownload = $request->query->get('forceDownload');
        $contentDisposition = \sprintf(
            '%s; filename="%s"',
            $forceDownload ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $attachment->getFile()->getName()
        );

        $response = new StreamedResponse(function () use ($attachment): void {
            $outputStream = fopen('php://output', 'w');
            $fileStream = $this->licenseesStorage->readStream($attachment->getFile()->getName());

            stream_copy_to_stream($fileStream, $outputStream);
        }, Response::HTTP_OK, [
            'Content-Type' => $attachment->getFile()->getMimeType(),
            'Content-Disposition' => $contentDisposition,
            'Content-Length' => $attachment->getFile()->getSize(),
        ]);
        $response->setLastModified($attachment->getUpdatedAt());

        return $response;
    }

    #[Route('/licensee/{id}/edit', name: 'app_licensee_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Licensee $licensee, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->assertHasValidLicense();

        /** @var User $user */
        $user = $this->getUser();

        $this->checkLicenseeAccess($user, $licensee);

        $form = $this->createForm(LicenseeFormType::class, $licensee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Licencié modifié avec succès.');

            return $this->redirectToRoute('app_licensee_profile', ['id' => $licensee->getId()]);
        }

        return $this->render('licensee/edit.html.twig', [
            'licensee' => $licensee,
            'form' => $form,
        ]);
    }

    /**
     * Check if current user has access to view/edit a licensee.
     */
    private function checkLicenseeAccess(User $user, Licensee $licensee): void
    {
        $hasAccess = $user->getLicensees()->contains($licensee)
            || $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_COACH');

        if (!$hasAccess && $this->isGranted('ROLE_CLUB_ADMIN')) {
            $hasAccess = $this->checkClubAdminAccess($user, $licensee);
        }

        if (!$hasAccess) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce profil.');
        }
    }

    /**
     * Check if club admin has access to licensee in same club.
     */
    private function checkClubAdminAccess(User $user, Licensee $licensee): bool
    {
        $currentSeason = $this->seasonHelper->getSelectedSeason();
        $userLicensees = $user->getLicensees();
        foreach ($userLicensees as $userLicensee) {
            if (!($userLicense = $userLicensee->getLicenseForSeason($currentSeason))) {
                continue;
            }

            if (!($targetLicense = $licensee->getLicenseForSeason($currentSeason)) instanceof \App\Entity\License) {
                continue;
            }

            if ($userLicense->getClub() === $targetLicense->getClub()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build results data grouped by season and category with charts.
     *
     * @return array{0: array<string, int>, 1: array<int, array<string, Chart>>}
     */
    private function buildResultsData(Licensee $licensee): array
    {
        $licenseeResults = $this->resultRepository->findForLicensee($licensee);
        [$resultsBySeason, $seasons] = $this->groupResultsBySeason($licenseeResults);
        krsort($seasons);

        $resultsCharts = [];
        foreach ($resultsBySeason as $season => $resultsByCategory) {
            foreach ($resultsByCategory as $category => $categoryResults) {
                $resultsCharts[$season][$category] = $this->buildResultChart($categoryResults['results']);
            }
        }

        ksort($resultsCharts);

        return [$seasons, $resultsCharts];
    }

    /**
     * Group results by season and category.
     *
     * @param Result[] $results
     *
     * @return array{0: array<int, array<string, array{max: int, results: Result[]}>>, 1: array<string, int>}
     */
    private function groupResultsBySeason(array $results): array
    {
        $resultsBySeason = [];
        $seasons = [];

        foreach ($results as $result) {
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

        return [$resultsBySeason, $seasons];
    }

    /**
     * Build chart for a category's results.
     *
     * @param Result[] $results
     */
    private function buildResultChart(array $results): Chart
    {
        $resultsTotals = array_map(static fn (Result $result): ?int => $result->getTotal(), $results);
        $lowestScore = min($resultsTotals);
        $bestScore = max($resultsTotals);
        $highest3Scores = $resultsTotals;
        sort($highest3Scores);
        $highest3Scores = \array_slice($highest3Scores, -3, 3);
        $averageScore = floor(array_sum($highest3Scores) / \count($highest3Scores));
        $scoreDiff = $bestScore - $lowestScore;

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => array_map(static fn (Result $result): ?string => $result->getEvent()->getName(), $results),
            'datasets' => [
                [
                    'label' => 'Score Total',
                    'data' => array_map(static fn (Result $result): ?int => $result->getTotal(), $results),
                    'backgroundColor' => array_map(
                        static function (Result $result) use ($lowestScore, $scoreDiff): string {
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
                        'font' => ['weight' => 'bold'],
                        'align' => 'end',
                    ],
                ],
            ],
        ]);

        $chart->setOptions([
            'aspectRatio' => 5 / 3,
            'scales' => [
                'y' => [
                    'min' => floor($lowestScore * 0.98),
                    'max' => floor($bestScore * 1.02),
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
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
                                'font' => ['weight' => 'bold'],
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
                                'font' => ['weight' => 'bold'],
                                'content' => \sprintf('Moyenne : %s', $averageScore),
                                'xAdjust' => 0,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        return $chart;
    }
}
