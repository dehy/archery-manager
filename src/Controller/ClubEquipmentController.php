<?php

declare(strict_types=1);

namespace App\Controller;

use App\DBAL\Types\ClubEquipmentType as ClubEquipmentTypeEnum;
use App\Entity\ClubEquipment;
use App\Entity\EquipmentLoan;
use App\Form\ClubEquipmentType;
use App\Form\EquipmentLoanType;
use App\Helper\ClubHelper;
use App\Helper\LicenseeHelper;
use App\Helper\SeasonHelper;
use App\Repository\ClubEquipmentRepository;
use App\Repository\EquipmentLoanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ClubEquipmentController extends BaseController
{
    private const string NO_ACTIVE_CLUB_ERROR = 'Aucun club actif trouvé';

    public function __construct(
        LicenseeHelper $licenseeHelper,
        SeasonHelper $seasonHelper,
        protected readonly ClubHelper $clubHelper,
        private readonly ClubEquipmentRepository $equipmentRepository,
        private readonly EquipmentLoanRepository $loanRepository,
    ) {
        parent::__construct($licenseeHelper, $seasonHelper);
    }

    #[Route('/club-equipment', name: 'app_club_equipment_index')]
    public function index(): Response
    {
        $this->assertHasValidLicense();
        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club) {
            throw $this->createNotFoundException(self::NO_ACTIVE_CLUB_ERROR);
        }

        $equipment = $this->equipmentRepository->findByClub($club);
        $availableEquipment = $this->equipmentRepository->findAvailableByClub($club);
        $loanedEquipment = $this->equipmentRepository->findCurrentlyLoanedByClub($club);

        return $this->render('club_equipment/index.html.twig', [
            'equipment' => $equipment,
            'availableEquipment' => $availableEquipment,
            'loanedEquipment' => $loanedEquipment,
            'club' => $club,
        ]);
    }

    #[Route('/club-equipment/new', name: 'app_club_equipment_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->assertHasValidLicense();

        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club) {
            throw $this->createNotFoundException(self::NO_ACTIVE_CLUB_ERROR);
        }

        $equipment = new ClubEquipment();
        $equipment->setClub($club);

        $form = $this->createForm(ClubEquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($equipment);
            $em->flush();

            $this->addFlash('success', 'Équipement ajouté avec succès');

            return $this->redirectToRoute('app_club_equipment_index');
        }

        return $this->render('club_equipment/new.html.twig', [
            'form' => $form,
            'club' => $club,
        ]);
    }

    #[Route('/club-equipment/{id}', name: 'app_club_equipment_show', requirements: ['id' => '\d+'])]
    public function show(ClubEquipment $equipment): Response
    {
        $this->assertHasValidLicense();

        // Verify equipment belongs to user's club
        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club || $equipment->getClub() !== $club) {
            throw $this->createAccessDeniedException();
        }

        $loanHistory = $this->loanRepository->findByEquipment($equipment);

        return $this->render('club_equipment/show.html.twig', [
            'equipment' => $equipment,
            'loanHistory' => $loanHistory,
        ]);
    }

    #[Route('/club-equipment/{id}/edit', name: 'app_club_equipment_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(ClubEquipment $equipment, Request $request, EntityManagerInterface $em): Response
    {
        $this->assertHasValidLicense();

        // Verify equipment belongs to user's club
        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club || $equipment->getClub() !== $club) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ClubEquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Équipement modifié avec succès');

            return $this->redirectToRoute('app_club_equipment_show', ['id' => $equipment->getId()]);
        }

        return $this->render('club_equipment/edit.html.twig', [
            'form' => $form,
            'equipment' => $equipment,
        ]);
    }

    #[Route('/club-equipment/{id}/delete', name: 'app_club_equipment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(ClubEquipment $equipment, EntityManagerInterface $em): Response
    {
        $this->assertHasValidLicense();

        // Verify equipment belongs to user's club
        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club || $equipment->getClub() !== $club) {
            throw $this->createAccessDeniedException();
        }

        // Check if equipment has active loans
        if ($equipment->isCurrentlyLoaned()) {
            $this->addFlash('danger', 'Impossible de supprimer un équipement actuellement prêté');

            return $this->redirectToRoute('app_club_equipment_show', ['id' => $equipment->getId()]);
        }

        $em->remove($equipment);
        $em->flush();

        $this->addFlash('success', 'Équipement supprimé avec succès');

        return $this->redirectToRoute('app_club_equipment_index');
    }

    #[Route('/club-equipment/{id}/loan', name: 'app_club_equipment_loan', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function loan(ClubEquipment $equipment, Request $request, EntityManagerInterface $em): Response
    {
        $this->assertHasValidLicense();

        // Verify equipment belongs to user's club
        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club || $equipment->getClub() !== $club) {
            throw $this->createAccessDeniedException();
        }

        // Check if all units are already loaned out
        if ($equipment->isFullyLoaned()) {
            $this->addFlash('danger', 'Tout le stock de cet équipement est actuellement prêté');

            return $this->redirectToRoute('app_club_equipment_show', ['id' => $equipment->getId()]);
        }

        $loan = new EquipmentLoan();
        $loan->setEquipment($equipment);
        $loan->setCreatedBy($this->getUser());
        $loan->setStartDate(new \DateTimeImmutable());

        $form = $this->createForm(EquipmentLoanType::class, $loan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $requestedQty = $loan->getQuantity();
            if ($requestedQty > $equipment->getAvailableQuantity()) {
                $this->addFlash(
                    'danger',
                    \sprintf(
                        'Quantité demandée (%d) supérieure au stock disponible (%d)',
                        $requestedQty,
                        $equipment->getAvailableQuantity(),
                    ),
                );

                return $this->render('club_equipment/loan.html.twig', [
                    'form' => $form,
                    'equipment' => $equipment,
                ]);
            }

            $em->persist($loan);
            $em->flush();

            $this->addFlash('success', 'Équipement prêté avec succès');

            return $this->redirectToRoute('app_club_equipment_show', ['id' => $equipment->getId()]);
        }

        return $this->render('club_equipment/loan.html.twig', [
            'form' => $form,
            'equipment' => $equipment,
        ]);
    }

    #[Route('/club-equipment/{id}/return', name: 'app_club_equipment_return', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function returnEquipment(ClubEquipment $equipment, EntityManagerInterface $em): Response
    {
        $this->assertHasValidLicense();

        // Verify equipment belongs to user's club
        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club || $equipment->getClub() !== $club) {
            throw $this->createAccessDeniedException();
        }

        $activeLoans = $equipment->getActiveLoans();
        if ($activeLoans->isEmpty()) {
            $this->addFlash('danger', 'Cet équipement n\'est pas actuellement prêté');

            return $this->redirectToRoute('app_club_equipment_show', ['id' => $equipment->getId()]);
        }

        // Return the first active loan (legacy single-loan behaviour)
        $activeLoans->first()->setReturnDate(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Prêt clôturé avec succès');

        return $this->redirectToRoute('app_club_equipment_show', ['id' => $equipment->getId()]);
    }

    #[Route('/club-equipment/loan/{loanId}/return', name: 'app_club_equipment_return_loan', requirements: ['loanId' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function returnLoan(
        #[MapEntity(id: 'loanId')]
        EquipmentLoan $loan,
        EntityManagerInterface $em,
    ): Response {
        $this->assertHasValidLicense();

        $equipment = $loan->getEquipment();

        // Verify equipment belongs to user's club
        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club || $equipment->getClub() !== $club) {
            throw $this->createAccessDeniedException();
        }

        if (!$loan->isActive()) {
            $this->addFlash('danger', 'Ce prêt est déjà clôturé');

            return $this->redirectToRoute('app_club_equipment_show', ['id' => $equipment->getId()]);
        }

        $loan->setReturnDate(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Prêt clôturé avec succès');

        return $this->redirectToRoute('app_club_equipment_show', ['id' => $equipment->getId()]);
    }

    #[Route('/club-equipment/loans', name: 'app_club_equipment_loans')]
    public function loans(Request $request): Response
    {
        $this->assertHasValidLicense();
        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club) {
            throw $this->createNotFoundException(self::NO_ACTIVE_CLUB_ERROR);
        }

        $filterType = $request->query->getString('type', '');
        $sort = $request->query->getString('sort', 'startDate');
        $dir = $request->query->getString('dir', 'DESC');

        // Validate sort & dir
        $validSorts = array_keys(EquipmentLoanRepository::SORTABLE_COLUMNS);
        if (!\in_array($sort, $validSorts, true)) {
            $sort = 'startDate';
        }
        $dir = 'ASC' === strtoupper($dir) ? 'ASC' : 'DESC';

        $activeLoans = $this->loanRepository->findActiveLoans(
            '' !== $filterType ? $filterType : null,
            $sort,
            $dir,
        );

        return $this->render('club_equipment/loans.html.twig', [
            'activeLoans' => $activeLoans,
            'club' => $club,
            'equipmentTypes' => ClubEquipmentTypeEnum::getChoices(),
            'filterType' => $filterType,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }
}
