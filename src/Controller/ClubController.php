<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\ClubHelper;
use App\Helper\LicenseHelper;
use App\Repository\GroupRepository;
use App\Repository\LicenseeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ClubController extends BaseController
{
    #[Route('/my-club', name: 'app_club_show')]
    public function show(
        ClubHelper $clubHelper,
        LicenseHelper $licenseHelper,
        GroupRepository $groupRepository,
        LicenseeRepository $licenseeRepository,
    ): Response {
        $this->assertHasValidLicense();

        $club = $clubHelper->activeClub();
        if (!$club) {
            throw $this->createNotFoundException('Aucun club actif trouvé');
        }

        $season = $this->seasonHelper->getSelectedSeason();

        // Récupération des groupes du club
        $groups = $groupRepository->findBy(['club' => $club], ['name' => 'ASC']);

        // Récupération des licenciés du club pour l'année courante
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

        return $this->render('club/show.html.twig', [
            'club' => $club,
            'groups' => $groups,
            'licensees' => $orderedLicensees,
            'season' => $season,
        ]);
    }
}
