<?php

namespace App\Controller\Admin;

use App\DBAL\Types\ContestType;
use App\DBAL\Types\DisciplineType;
use App\DBAL\Types\EventType;
use App\Entity\Event;
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
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EventCrudController extends AbstractCrudController
{
    public function __construct(protected AdminUrlGenerator $urlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Event::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            DateTimeField::new('startsAt'),
            DateTimeField::new('endsAt'),
            ChoiceField::new('type')
                ->setChoices(EventType::getChoices())
                ->renderExpanded(),
            ChoiceField::new('discipline')->setChoices(
                DisciplineType::getChoices(),
            ),
            ChoiceField::new('contestType')->setChoices(
                ContestType::getChoices(),
            ),
            TextField::new('address'),
        ];
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['startsAt' => 'ASC', 'endsAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $importResultArcScoresAction = Action::new(
            'resultArcImport',
            'Import results',
            'fas fa-file-import',
        )->linkToCrudAction('importResults');

        $seeResultsAction = Action::new(
            'showEventResults',
            'Results',
        )->linkToUrl(function (Event $event) {
            return $this->urlGenerator
                ->unsetAll()
                ->setController(ResultCrudController::class)
                ->set('filters[event][comparison]', '=')
                ->set('filters[event][value]', $event->getId());
        });

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $seeResultsAction)
            ->add(Crud::PAGE_DETAIL, $importResultArcScoresAction);
    }

    /**
     * @throws FilesystemException
     * @throws NonUniqueResultException
     */
    public function importResults(
        AdminContext           $context,
        Request                $request,
        ResultArcParser        $resultArcParser,
        EntityManagerInterface $entityManager,
        FilesystemOperator     $eventsStorage,
    ): Response {
        /** @var Event $event */
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
            $sanitizedEventName = mb_ereg_replace(
                '([^\w\s\d\-_~,;\[\]\(\).])',
                '-',
                $event->getName(),
            );
            $filepath = sprintf(
                '%s/%s - %s - Résultats.pdf',
                $event->getId(),
                $event->getStartsAt()->format('Y-m-d'),
                $sanitizedEventName,
            );
            $eventsStorage->write($filepath, $file->getContent());
            $event->setResultFilepath($filepath);
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
                ] = Result::distanceForContestTypeAndActivity(
                    $event->getContestType(),
                    $event->getDiscipline(),
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
                    ->generateUrl() .
                '&filters[event][comparison]==&filters[event][value]=' .
                $event->getId(),
            );
        }

        return $this->render('admin/event/importResultArc.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }
}
