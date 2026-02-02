<?php

declare(strict_types=1);

namespace App\Controller;

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
            'currentLoan' => $equipment->getCurrentLoan(),
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

        // Check if equipment is already loaned
        if ($equipment->isCurrentlyLoaned()) {
            $this->addFlash('danger', 'Cet équipement est déjà prêté');

            return $this->redirectToRoute('app_club_equipment_show', ['id' => $equipment->getId()]);
        }

        $loan = new EquipmentLoan();
        $loan->setEquipment($equipment);
        $loan->setCreatedBy($this->getUser());
        $loan->setStartDate(new \DateTimeImmutable());

        $form = $this->createForm(EquipmentLoanType::class, $loan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $equipment->setIsAvailable(false);

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

        $currentLoan = $equipment->getCurrentLoan();
        if (!$currentLoan instanceof EquipmentLoan) {
            $this->addFlash('danger', 'Cet équipement n\'est pas actuellement prêté');

            return $this->redirectToRoute('app_club_equipment_show', ['id' => $equipment->getId()]);
        }

        $currentLoan->setReturnDate(new \DateTimeImmutable());
        $equipment->setIsAvailable(true);

        $em->flush();

        $this->addFlash('success', 'Équipement retourné avec succès');

        return $this->redirectToRoute('app_club_equipment_show', ['id' => $equipment->getId()]);
    }

    #[Route('/club-equipment/loans', name: 'app_club_equipment_loans')]
    public function loans(): Response
    {
        $this->assertHasValidLicense();
        $club = $this->clubHelper->activeClub();
        if (!$club instanceof \App\Entity\Club) {
            throw $this->createNotFoundException(self::NO_ACTIVE_CLUB_ERROR);
        }

        $activeLoans = $this->loanRepository->findActiveLoans();

        return $this->render('club_equipment/loans.html.twig', [
            'activeLoans' => $activeLoans,
            'club' => $club,
        ]);
    }
}
