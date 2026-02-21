<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\EquipmentLoan;
use App\Entity\Licensee;
use App\Helper\ClubHelper;
use App\Helper\SeasonHelper;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipmentLoanType extends AbstractType
{
    public function __construct(
        private readonly ClubHelper $clubHelper,
        private readonly SeasonHelper $seasonHelper,
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $club = $this->clubHelper->activeClub();
        $season = $this->seasonHelper->getSelectedSeason();

        $builder
            ->add('borrower', EntityType::class, [
                'label' => 'Emprunteur',
                'class' => Licensee::class,
                'query_builder' => static fn (EntityRepository $er): \Doctrine\ORM\QueryBuilder => $er->createQueryBuilder('l')
                    ->leftJoin('l.licenses', 'li')
                    ->where('li.club = :club')
                    ->andWhere('li.season = :season')
                    ->setParameter('club', $club)
                    ->setParameter('season', $season)
                    ->orderBy('l.firstname', 'ASC')
                    ->addOrderBy('l.lastname', 'ASC'),
                'choice_label' => static fn (Licensee $licensee): string => $licensee->getFirstname().' '.$licensee->getLastname(),
                'placeholder' => 'Sélectionnez un licencié',
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'data' => new \DateTimeImmutable(),
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité à prêter',
                'data' => 1,
                'attr' => [
                    'min' => 1,
                ],
                'help' => 'Nombre d\'unités à prêter',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Remarques, conditions particulières...',
                ],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EquipmentLoan::class,
        ]);
    }
}
