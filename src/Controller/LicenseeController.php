<?php

declare(strict_types=1);

namespace App\Controller;

use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\LicenseActivityType;
use App\Entity\License;
use App\Entity\Licensee;
use App\Entity\LicenseeAttachment;
use App\Entity\Result;
use App\Entity\Season;
use App\Entity\User;
use App\Form\Type\LicenseeFormType;
use App\Form\Type\ProfilePictureType;
use App\Helper\LicenseeHelper;
use App\Helper\LicenseHelper;
use App\Helper\ResultHelper;
use App\Helper\SeasonHelper;
use App\Repository\EquipmentLoanRepository;
use App\Repository\GroupRepository;
use App\Repository\LicenseeRepository;
use App\Repository\PracticeAdviceRepository;
use App\Repository\ResultRepository;
use App\Security\Voter\LicenseeAccessVoter;
use App\Service\ProfilePictureManager;
use App\Twig\LicenseeDisplayExtension;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class LicenseeController extends BaseController
{
    public function __construct(LicenseeHelper $licenseeHelper, SeasonHelper $seasonHelper, private readonly LicenseeRepository $licenseeRepository, private readonly LicenseHelper $licenseHelper, private readonly GroupRepository $groupRepository, private readonly ResultRepository $resultRepository, private readonly EquipmentLoanRepository $loanRepository, private readonly ChartBuilderInterface $chartBuilder, private readonly FilesystemOperator $licenseesStorage, private readonly EntityManagerInterface $entityManager, private readonly ProfilePictureManager $pictureManager, private readonly PracticeAdviceRepository $practiceAdviceRepository, private readonly LicenseeDisplayExtension $licenseeDisplay)
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

        // Search and activity (redesigned directory). The search only looks at the name the viewer may see:
        // a member searching "Martin" must not find "Camille M." through her hidden last name.
        $search = $this->searchable($request->query->getString('q'));
        if ('' !== $search) {
            $licensees = $licensees->filter(fn (Licensee $licensee): bool => str_contains(
                $this->searchable($this->licenseeDisplay->getLicenseeDisplayName($licensee)),
                $search,
            ));
        }

        $activity = $request->query->getString('activity');
        if (LicenseActivityType::isValueExist($activity)) {
            $licensees = $licensees->filter(static fn (Licensee $licensee): bool => \in_array(
                $activity,
                $licensee->getLicenseForSeason($season)?->getActivities() ?? [],
                true,
            ));
        }

        /** @var ArrayCollection<int, Licensee> $orderedLicensees */
        $orderedLicensees = $licensees->matching(
            Criteria::create()->orderBy([
                'firstname' => Order::Ascending,
                'lastname' => Order::Ascending,
            ]),
        );

        // Récupérer tous les groupes pour l'affichage des filtres
        $allGroups = $this->groupRepository->findBy(['club' => $club], ['name' => 'ASC']);

        /** @var User $user */
        $user = $this->getUser();

        return $this->render($user->hasNewInterface() ? 'licensee/index_next.html.twig' : 'licensee/index.html.twig', [
            'search' => $request->query->getString('q'),
            'activity' => $activity,
            'activities' => LicenseActivityType::getChoices(),
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
    #[Route('/licensee/{id}', name: 'app_licensee_profile', requirements: ['id' => '\d+'], methods: ['GET']),]
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

        $pictureForm = null;
        if ($this->isGranted(LicenseeAccessVoter::EDIT_PICTURE, $licensee)) {
            $pictureForm = $this->createPictureForm($licensee);
        }

        $parameters = [
            'licensee' => $licensee,
            'seasons' => $seasons,
            'results_charts' => $resultsCharts,
            'picture_form' => $pictureForm?->createView(),
            'activeLoans' => $activeLoans,
        ];
        $template = 'licensee/show.html.twig';
        if ($user->hasNewInterface()) {
            $template = 'licensee/show_next.html.twig';
            // Advice is personal: only the archer's own account reads it (a 404 for anybody else)
            $parameters['latestAdvice'] = $user->getLicensees()->contains($licensee)
                ? $this->practiceAdviceRepository->findForLicenseeNewestFirst($licensee, false)[0] ?? null
                : null;
        }

        return $this->render($template, $parameters);
    }

    #[Route('/licensee/{id}/picture', name: 'app_licensee_picture_upload', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function uploadPicture(Licensee $licensee, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted(LicenseeAccessVoter::EDIT_PICTURE, $licensee);

        $form = $this->createPictureForm($licensee);
        $form->handleRequest($request);

        $file = $form->get('picture')->getData();
        if ($form->isSubmitted() && $form->isValid() && $file instanceof UploadedFile) {
            $this->pictureManager->replace($licensee, $file);
            $this->addFlash('success', 'Photo de profil mise à jour.');
        } else {
            $error = $form->getErrors(true)->current();
            $this->addFlash('danger', $error instanceof FormError ? $error->getMessage() : 'La photo n\'a pas pu être enregistrée.');
        }

        return $this->redirectToRoute('app_licensee_profile', ['id' => $licensee->getId()]);
    }

    #[Route('/licensee/{id}/picture/delete', name: 'app_licensee_picture_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deletePicture(Licensee $licensee, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted(LicenseeAccessVoter::EDIT_PICTURE, $licensee);

        if ($this->isCsrfTokenValid('delete_picture_'.$licensee->getId(), $request->getPayload()->getString('_token'))) {
            $this->pictureManager->remove($licensee);
            $this->addFlash('success', 'Photo de profil supprimée.');
        } else {
            $this->addFlash('danger', 'Jeton de sécurité invalide, veuillez réessayer.');
        }

        return $this->redirectToRoute('app_licensee_profile', ['id' => $licensee->getId()]);
    }

    private function createPictureForm(Licensee $licensee): FormInterface
    {
        return $this->createForm(ProfilePictureType::class, null, [
            'action' => $this->generateUrl('app_licensee_picture_upload', ['id' => $licensee->getId()]),
            'method' => 'POST',
        ]);
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
    public function edit(Licensee $licensee, Request $request): Response
    {
        $this->assertHasValidLicense();

        /** @var User $user */
        $user = $this->getUser();

        $this->checkLicenseeAccess($user, $licensee);

        $form = $this->createForm(LicenseeFormType::class, $licensee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Licencié modifié avec succès.');

            return $this->redirectToRoute('app_licensee_profile', ['id' => $licensee->getId()]);
        }

        return $this->render('licensee/edit.html.twig', [
            'licensee' => $licensee,
            'form' => $form,
        ]);
    }

    /**
     * Assert that the current user is allowed to view/edit the given licensee.
     *
     * Access is granted when ANY of the following is true:
     *   1. The licensee belongs to the authenticated user.
     *   2. The user holds ROLE_ADMIN (unrestricted access).
     *   3. The user holds ROLE_CLUB_ADMIN or ROLE_COACH AND shares the same
     *      club as the licensee in the currently selected season.
     */
    /** Lower case, without accents or extra spaces: "Héloïse " and "heloise" match. */
    private function searchable(string $text): string
    {
        $ascii = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', trim($text));

        return (string) preg_replace('/\s+/', ' ', false === $ascii ? mb_strtolower(trim($text)) : $ascii);
    }

    private function checkLicenseeAccess(User $user, Licensee $licensee): void
    {
        $hasAccess = $user->getLicensees()->contains($licensee) || $this->isGranted('ROLE_ADMIN');

        if (!$hasAccess && ($this->isGranted('ROLE_CLUB_ADMIN') || $this->isGranted('ROLE_COACH'))) {
            $hasAccess = $this->hasSameClubAccess($user, $licensee);
        }

        if (!$hasAccess) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce profil.');
        }
    }

    /**
     * Return true when the user and the target licensee share the same club
     * in the currently selected season.
     *
     * Used to scope ROLE_CLUB_ADMIN and ROLE_COACH access to their own club.
     */
    private function hasSameClubAccess(User $user, Licensee $licensee): bool
    {
        $currentSeason = $this->seasonHelper->getSelectedSeason();
        $userLicensees = $user->getLicensees();
        foreach ($userLicensees as $userLicensee) {
            if (!($userLicense = $userLicensee->getLicenseForSeason($currentSeason))) {
                continue;
            }

            if (!($targetLicense = $licensee->getLicenseForSeason($currentSeason)) instanceof License) {
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
