<?php

namespace App\Controller\Admin;

use App\DBAL\Types\PracticeLevelType;
use App\Entity\Applicant;
use App\Form\PreRegistrationSettingsType;
use App\Repository\ApplicantRepository;
use Dmishh\SettingsBundle\Manager\SettingsManager;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApplicantCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Applicant::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Pré-inscrit')
            ->setEntityLabelInPlural('Pré-inscrits')
            ->setPaginatorPageSize(150);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm()
                ->setTemplatePath('admin/crud/fields/id.html.twig'),
            BooleanField::new('renewal', 'Renouvellement')->renderAsSwitch(
                false,
            ),
            BooleanField::new(
                'onWaitingList',
                "Liste d'attente",
            )->renderAsSwitch(false),
            DateTimeField::new('registeredAt', 'Soumis le'),
            TextField::new('lastname', 'Nom'),
            TextField::new('firstname', 'Prénom'),
            DateField::new('birthdate', 'Date de naissance'),
            IntegerField::new('age', 'Âge')->hideOnForm(),
            ChoiceField::new('practiceLevel', 'Niveau de pratique')->setChoices(
                PracticeLevelType::getChoices(),
            ),
            TextField::new('licenseNumber', 'N° Licence'),
            BooleanField::new('tournament', 'Compétition'),
            EmailField::new('email'),
            TelephoneField::new('phoneNumber', 'Téléphone'),
            TextField::new('comment', 'Commentaire'),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('renewal')
            ->add('season')
            ->add('onWaitingList');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    /**
     * @Route("/admin/applicants/stats", name="app_admin_applicants_stats")
     */
    public function applicantStatistics(
        Request $request,
        ApplicantRepository $applicantRepository,
        SettingsManager $settingsManager,
        AdminUrlGenerator $adminUrlGenerator,
    ): Response {
        $settingsForm = $this->createForm(
            PreRegistrationSettingsType::class,
        )->add('Enregistrer', SubmitType::class);

        $settingsForm->handleRequest($request);
        if ($settingsForm->isSubmitted() && $settingsForm->isValid()) {
            $data = $settingsForm->getData();
            $settingsManager->set(
                'pre_registration_waiting_list_activated',
                $data['waitingListActivated'] ?? false,
            );

            return $this->redirect($adminUrlGenerator->generateUrl());
        }

        $applicants = $applicantRepository->findBy(['season' => 2023]);
        $new = $renewal = 0;
        $newAges = [
            '10 ans et moins' => 0,
            'Entre 11 et 12 ans' => 0,
            'Entre 13 et 14 ans' => 0,
            'Entre 15 et 17 ans' => 0,
            'Entre 18 et 20 ans' => 0,
            'Entre 21 et 39 ans' => 0,
            'Entre 40 et 59 ans' => 0,
            '60 ans et plus' => 0,
        ];
        foreach ($applicants as $applicant) {
            $age = $applicant->getAge();

            $new += $applicant->isRenewal() ? 0 : 1;
            $renewal += $applicant->isRenewal() ? 1 : 0;

            if (!$applicant->isRenewal()) {
                if ($age <= 10) {
                    ++$newAges['10 ans et moins'];
                } elseif ($age >= 11 && $age <= 12) {
                    ++$newAges['Entre 11 et 12 ans'];
                } elseif ($age >= 13 && $age <= 14) {
                    ++$newAges['Entre 13 et 14 ans'];
                } elseif ($age >= 15 && $age <= 17) {
                    ++$newAges['Entre 15 et 17 ans'];
                } elseif ($age >= 18 && $age <= 20) {
                    ++$newAges['Entre 18 et 20 ans'];
                } elseif ($age >= 21 && $age <= 39) {
                    ++$newAges['Entre 21 et 39 ans'];
                } elseif ($age >= 40 && $age <= 59) {
                    ++$newAges['Entre 40 et 59 ans'];
                } elseif ($age >= 60) {
                    ++$newAges['60 ans et plus'];
                }
            }
        }

        $stats = [
            'new' => $new,
            'renewal' => $renewal,
            'total' => \count($applicants),
            'newAges' => $newAges,
        ];

        return $this->render('admin/pre_registration/stats.html.twig', [
            'stats' => $stats,
            'applicants' => $applicants,
            'form' => $settingsForm->createView(),
        ]);
    }
}
