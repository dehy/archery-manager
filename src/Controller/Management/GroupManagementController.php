<?php

declare(strict_types=1);

namespace App\Controller\Management;

use App\Controller\BaseController;
use App\Entity\Club;
use App\Entity\Group;
use App\Entity\Licensee;
use App\Form\Type\GroupMemberActionType;
use App\Form\Type\GroupType;
use App\Helper\LicenseeHelper;
use App\Helper\LicenseHelper;
use App\Helper\SeasonHelper;
use App\Repository\LicenseeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CLUB_ADMIN')]
class GroupManagementController extends BaseController
{
    public function __construct(LicenseeHelper $licenseeHelper, SeasonHelper $seasonHelper, private readonly LicenseeRepository $licenseeRepository, private readonly LicenseHelper $licenseHelper)
    {
        parent::__construct($licenseeHelper, $seasonHelper);
    }

    #[Route('/groups/{id}/manage', name: 'app_group_manage', requirements: ['id' => '\d+'])]
    public function manage(Group $group): Response
    {
        $club = $this->currentClub();

        if ($group->getClub() !== $club) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas gérer ce groupe.');
        }

        $season = $this->seasonHelper->getSelectedSeason();
        $allLicensees = $this->licenseeRepository->findByLicenseYear($club, $season);

        $groupMembers = [];
        $availableLicensees = [];

        foreach ($allLicensees as $licensee) {
            if ($group->getLicensees()->contains($licensee)) {
                $groupMembers[] = $licensee;
            } else {
                $availableLicensees[] = $licensee;
            }
        }

        $addMemberForm = $this->createForm(GroupMemberActionType::class, null, [
            'action' => $this->generateUrl('app_group_add_member', ['id' => $group->getId()]),
        ]);
        $removeMemberForm = $this->createForm(GroupMemberActionType::class, null, [
            'action' => $this->generateUrl('app_group_remove_member', ['id' => $group->getId()]),
        ]);

        return $this->render('management/group/manage.html.twig', [
            'group' => $group,
            'groupMembers' => $groupMembers,
            'availableLicensees' => $availableLicensees,
            'season' => $season,
            'addMemberForm' => $addMemberForm,
            'removeMemberForm' => $removeMemberForm,
        ]);
    }

    #[Route('/groups/{id}/add-member', name: 'app_group_add_member', methods: ['POST'])]
    public function addMember(Group $group, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $licenseeOrError = $this->resolveMemberAction($group, $request);
        if ($licenseeOrError instanceof JsonResponse) {
            return $licenseeOrError;
        }

        $licensee = $licenseeOrError;

        if (!$licensee->getClubs()->contains($this->currentClub())) {
            return new JsonResponse(['error' => "Ce licencié n'appartient pas à votre club"], Response::HTTP_BAD_REQUEST);
        }

        if ($group->getLicensees()->contains($licensee)) {
            return new JsonResponse(['error' => 'Ce licencié fait déjà partie du groupe'], Response::HTTP_BAD_REQUEST);
        }

        $group->addLicensee($licensee);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => \sprintf('%s a été ajouté(e) au groupe %s', $licensee->getFullname(), $group->getName()),
        ]);
    }

    #[Route('/groups/{id}/remove-member', name: 'app_group_remove_member', methods: ['POST'])]
    public function removeMember(Group $group, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $licenseeOrError = $this->resolveMemberAction($group, $request);
        if ($licenseeOrError instanceof JsonResponse) {
            return $licenseeOrError;
        }

        $licensee = $licenseeOrError;

        if (!$group->getLicensees()->contains($licensee)) {
            return new JsonResponse(['error' => 'Ce licencié ne fait pas partie du groupe'], Response::HTTP_BAD_REQUEST);
        }

        $group->removeLicensee($licensee);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => \sprintf('%s a été retiré(e) du groupe %s', $licensee->getFullname(), $group->getName()),
        ]);
    }

    #[Route('/groups/create', name: 'app_group_create')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $club = $this->currentClub();

        $group = new Group();
        $group->setClub($club);

        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($group);
            $entityManager->flush();

            $this->addFlash('success', \sprintf('Le groupe "%s" a été créé avec succès.', $group->getName()));

            return $this->redirectToRoute('app_group_manage', ['id' => $group->getId()]);
        }

        return $this->render('management/group/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function currentClub(): Club
    {
        $this->assertHasValidLicense();

        return $this->licenseHelper->getCurrentLicenseeCurrentLicense()->getClub();
    }

    private function resolveMemberAction(Group $group, Request $request): JsonResponse|Licensee
    {
        if ($group->getClub() !== $this->currentClub()) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $form = $this->createForm(GroupMemberActionType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse(['error' => 'Requête invalide'], Response::HTTP_BAD_REQUEST);
        }

        $licensee = $this->licenseeRepository->find((int) $form->get('licenseeId')->getData());

        return $licensee ?? new JsonResponse(['error' => 'Licencié non trouvé'], Response::HTTP_NOT_FOUND);
    }
}
