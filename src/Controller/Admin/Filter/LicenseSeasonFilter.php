<?php

namespace App\Controller\Admin\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class LicenseSeasonFilter implements FilterInterface
{
    use FilterTrait;

    public static function new($label = null): self
    {
        return (new self())
            ->setFilterFqcn(self::class)
            ->setProperty('season')
            ->setLabel($label)
            ->setFormType(IntegerType::class);
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
            ->andWhere('license.season = :season')
            ->setParameter('season', $filterDataDto->getValue());
    }
}
