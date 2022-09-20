<?php

namespace App\Controller\Admin;

use App\Entity\PracticeAdvice;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PracticeAdviceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PracticeAdvice::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('licensee'),
            TextField::new('title'),
            TextEditorField::new('advice'),
            AssociationField::new('author'),
            DateTimeField::new('createdAt')->hideOnForm(),
            BooleanField::new('archivedAt', 'Archived')
                ->hideOnForm()
                ->renderAsSwitch(false),
        ];
    }
}
