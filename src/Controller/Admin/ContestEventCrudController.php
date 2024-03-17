<?php

namespace App\Controller\Admin;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventAttachmentType;
use App\DBAL\Types\EventType;
use App\Entity\ContestEvent;
use App\Entity\Event;
use App\Entity\EventAttachment;
use App\Entity\Licensee;
use App\Entity\Result;
use App\Repository\LicenseeRepository;
use App\Repository\ResultRepository;
use App\Scrapper\ResultArcParser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContestEventCrudController extends AbstractCrudController
{
    public function __construct(protected AdminUrlGenerator $urlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return ContestEvent::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            DateField::new('startDate'),
            TimeField::new('startTime'),
            DateField::new('endDate'),
            TimeField::new('endTime'),
            BooleanField::new('fullDayEvent')->renderAsSwitch(false),
            ChoiceField::new('contestType')->setChoices(
                ContestType::getChoices(),
            ),
            ChoiceField::new('discipline')->setChoices(
                DisciplineType::getChoices(),
            ),
            TextField::new('address'),
            TextField::new('latitude'),
            TextField::new('longitude'),
            AssociationField::new('assignedGroups'),
            BooleanField::new('hasMandate', 'Mandat')->renderAsSwitch(false)->hideOnForm(),
            BooleanField::new('hasResults', 'Résultats')->renderAsSwitch(false)->hideOnForm(),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort([
            'startDate' => 'DESC',
            'startTime' => 'DESC',
            'endDate' => 'DESC',
            'endTime' => 'DESC'
        ]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add(EntityFilter::new('club'))
            ->add(ChoiceFilter::new('type')->setChoices(EventType::getChoices()))
            ->add(ChoiceFilter::new('discipline')->setChoices(DisciplineType::getChoices()));
    }

    public function configureActions(Actions $actions): Actions
    {
        $attachmentsAction = Action::new(
            'eventAttachments',
            'Pièces jointes',
            'fa-solid fa-paperclip'
        )->linkToUrl(fn (Event $event) => $this->urlGenerator
            ->unsetAll()
            ->setController(EventAttachmentCrudController::class)
            ->set('filters[event][comparison]', '=')
            ->set('filters[event][value]', $event->getId()));

        $importResultArcScoresAction = Action::new(
            'resultArcImport',
            'Importer résultats',
            'fa-solid fa-file-import',
        )->linkToCrudAction('importResults');

        $seeResultsAction = Action::new(
            'showEventResults',
            'Results',
        )->linkToUrl(fn (Event $event) => $this->urlGenerator
            ->unsetAll()
            ->setController(ResultCrudController::class)
            ->set('filters[event][comparison]', '=')
            ->set('filters[event][value]', $event->getId()));

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $attachmentsAction)
            ->add(Crud::PAGE_INDEX, $seeResultsAction)
            ->add(Crud::PAGE_INDEX, $importResultArcScoresAction)
            ->add(Crud::PAGE_DETAIL, $importResultArcScoresAction);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function importResults(
        AdminContext $context,
        Request $request,
        ResultArcParser $resultArcParser,
        EntityManagerInterface $entityManager,
        FilesystemOperator $eventsStorage,
    ): Response {
        /** @var ContestEvent $event */
        $event = $context->getEntity()->getInstance();

        $form = $this->createFormBuilder()
            ->add('event', TextType::class, [
                'disabled' => true,
                'data' => $event->__toString(),
            ])
            ->add('file', FileType::class, [
                'label' => 'Result‘Arc file',
            ])
            ->add('import', SubmitType::class, [
                'disabled' => !$event->canImportResults(),
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();

            $eventAttachment = new EventAttachment();
            $eventAttachment->setType(EventAttachmentType::RESULTS);
            $eventAttachment->setUploadedFile($file);
            $eventAttachment->setEvent($event);

            $entityManager->persist($eventAttachment);
            $entityManager->flush();

            $resultLines = $resultArcParser->parseFile($file);

            /** @var LicenseeRepository $licenseeRepository */
            $licenseeRepository = $entityManager->getRepository(
                Licensee::class,
            );

            /** @var ResultRepository $resultRepository */
            $resultRepository = $entityManager->getRepository(Result::class);

            foreach ($resultLines as $line) {
                $licensee = $licenseeRepository->findOneByCode($line->fftaCode);
                if (!$licensee) {
                    continue;
                }

                [
                    $distance,
                    $targetSize,
                ] = Result::distanceForContestAndActivity(
                    $event,
                    $line->activity,
                    $line->ageCategory,
                );

                $existingResult = $resultRepository->findOneBy([
                    'event' => $event->getId(),
                    'licensee' => $licensee->getId(),
                ]);
                if ($existingResult) {
                    $result = $existingResult;
                } else {
                    $result = (new Result())
                        ->setEvent($event)
                        ->setLicensee($licensee);

                    $entityManager->persist($result);
                }
                $result
                    ->setActivity($line->activity)
                    ->setDiscipline($event->getDiscipline())
                    ->setTotal($line->score)
                    ->setDistance($distance)
                    ->setTargetSize($targetSize);
            }
            $entityManager->flush();

            return $this->redirect(
                $this->urlGenerator
                    ->unsetAll()
                    ->setController(ResultCrudController::class)
                    ->setAction(Action::INDEX)
                    ->generateUrl().
                '&filters[event][comparison]==&filters[event][value]='.
                $event->getId(),
            );
        }

        return $this->render('admin/event/importResultArc.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }
}
