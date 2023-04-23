<?php

namespace App\Controller\Admin\Filter;

use App\Entity\Club;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ClubFilter implements FilterInterface
{
    use FilterTrait;

    public static function new($label = null): self
    {
        return (new self())
            ->setFilterFqcn(self::class)
            ->setProperty('club')
            ->setLabel($label)
            ->setFormType(EntityType::class)
            ->setFormTypeOption('class', Club::class);
    }

    public function apply(
        QueryBuilder $queryBuilder,
        FilterDataDto $filterDataDto,
        ?FieldDto $fieldDto,
        EntityDto $entityDto,
    ): void {
        $queryBuilder
            ->join(
                sprintf('%s.licenses', $filterDataDto->getEntityAlias()),
                'license',
            )
            ->andWhere('license.club = :club')
            ->setParameter('club', $filterDataDto->getValue());
    }
}
