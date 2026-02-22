<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Licensee;
use App\Helper\ClubHelper;
use App\Repository\GroupRepository;
use App\Repository\LicenseeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ClubController extends BaseController
{
    public function __construct(\App\Helper\LicenseeHelper $licenseeHelper, \App\Helper\SeasonHelper $seasonHelper, private readonly ClubHelper $clubHelper, private readonly GroupRepository $groupRepository, private readonly LicenseeRepository $licenseeRepository)
    {
        parent::__construct($licenseeHelper, $seasonHelper);
    }

    #[Route('/my-club', name: 'app_club_show')]
    public function show(): Response
    {
        $this->assertHasValidLicense();
        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club) {
            throw $this->createNotFoundException('Aucun club actif trouvé');
        }

        $season = $this->seasonHelper->getSelectedSeason();
        // Récupération des groupes du club
        $groups = $this->groupRepository->findBy(['club' => $club], ['name' => 'ASC']);
        // Récupération des licenciés du club pour l'année courante
        $licensees = new ArrayCollection(
            $this->licenseeRepository->findByLicenseYear($club, $season),
        );
        /** @var ArrayCollection<int, Licensee> $orderedLicensees */
        $orderedLicensees = $licensees->matching(
            Criteria::create()->orderBy([
                'firstname' => 'ASC',
                'lastname' => 'ASC',
            ]),
        );
        // Compter les licenciés sans groupe
        $noGroupLicenseesCount = $licensees->filter(static fn ($licensee) => $licensee->getGroups()->isEmpty())->count();

        return $this->render('club/show.html.twig', [
            'club' => $club,
            'groups' => $groups,
            'licensees' => $orderedLicensees,
            'season' => $season,
            'noGroupLicenseesCount' => $noGroupLicenseesCount,
        ]);
    }
}
