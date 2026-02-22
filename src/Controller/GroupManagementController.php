<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Group;
use App\Form\Type\GroupType;
use App\Helper\LicenseHelper;
use App\Repository\LicenseeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class GroupManagementController extends BaseController
{
    public function __construct(\App\Helper\LicenseeHelper $licenseeHelper, \App\Helper\SeasonHelper $seasonHelper, private readonly LicenseeRepository $licenseeRepository, private readonly LicenseHelper $licenseHelper)
    {
        parent::__construct($licenseeHelper, $seasonHelper);
    }

    #[Route('/admin/groups/{id}/manage', name: 'app_group_manage', requirements: ['id' => '\d+'])]
    public function manage(
        Group $group,
    ): Response {
        $this->assertHasValidLicense();

        $season = $this->seasonHelper->getSelectedSeason();
        $club = $this->licenseHelper->getCurrentLicenseeCurrentLicense()->getClub();

        // Vérifier que le groupe appartient au club de l'utilisateur
        if ($group->getClub() !== $club) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas gérer ce groupe.');
        }

        // Récupérer tous les licenciés du club pour la saison courante
        $allLicensees = $this->licenseeRepository->findByLicenseYear($club, $season);

        // Séparer les licenciés membres et non-membres du groupe
        $groupMembers = [];
        $availableLicensees = [];

        foreach ($allLicensees as $licensee) {
            if ($group->getLicensees()->contains($licensee)) {
                $groupMembers[] = $licensee;
            } else {
                $availableLicensees[] = $licensee;
            }
        }

        return $this->render('group/manage.html.twig', [
            'group' => $group,
            'groupMembers' => $groupMembers,
            'availableLicensees' => $availableLicensees,
            'season' => $season,
        ]);
    }

    #[Route('/admin/groups/{id}/add-member', name: 'app_group_add_member', methods: ['POST'])]
    public function addMember(
        Group $group,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->assertHasValidLicense();

        $club = $this->licenseHelper->getCurrentLicenseeCurrentLicense()->getClub();

        // Vérifier que le groupe appartient au club de l'utilisateur
        if ($group->getClub() !== $club) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $licenseeId = $request->request->get('licenseeId');
        $licensee = $this->licenseeRepository->find($licenseeId);

        if (!$licensee) {
            return new JsonResponse(['error' => 'Licencié non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier que le licencié appartient au club
        if (!$licensee->getClubs()->contains($club)) {
            return new JsonResponse(['error' => 'Ce licencié n\'appartient pas à votre club'], Response::HTTP_BAD_REQUEST);
        }

        // Ajouter le licencié au groupe s'il n'y est pas déjà
        if (!$group->getLicensees()->contains($licensee)) {
            $group->addLicensee($licensee);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => \sprintf('%s a été ajouté(e) au groupe %s', $licensee->getFullname(), $group->getName()),
            ]);
        }

        return new JsonResponse(['error' => 'Ce licencié fait déjà partie du groupe'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/admin/groups/{id}/remove-member', name: 'app_group_remove_member', methods: ['POST'])]
    public function removeMember(
        Group $group,
        Request $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $this->assertHasValidLicense();

        $club = $this->licenseHelper->getCurrentLicenseeCurrentLicense()->getClub();

        // Vérifier que le groupe appartient au club de l'utilisateur
        if ($group->getClub() !== $club) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $licenseeId = $request->request->get('licenseeId');
        $licensee = $this->licenseeRepository->find($licenseeId);

        if (!$licensee) {
            return new JsonResponse(['error' => 'Licencié non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Retirer le licencié du groupe s'il en fait partie
        if ($group->getLicensees()->contains($licensee)) {
            $group->removeLicensee($licensee);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => \sprintf('%s a été retiré(e) du groupe %s', $licensee->getFullname(), $group->getName()),
            ]);
        }

        return new JsonResponse(['error' => 'Ce licencié ne fait pas partie du groupe'], Response::HTTP_BAD_REQUEST);
    }

    #[Route('/admin/groups/create', name: 'app_group_create')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->assertHasValidLicense();

        $club = $this->licenseHelper->getCurrentLicenseeCurrentLicense()->getClub();

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

        return $this->render('group/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
