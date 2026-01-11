<?php

namespace App\Controller\Admin;

use App\Entity\ResetPasswordRequest;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ResetPasswordRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ResetPasswordRequest::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('user'),
            TextField::new('selector')->hideOnForm(),
            TextField::new('hashedToken'),
            DateTimeField::new('requestedAt'),
            DateTimeField::new('expiresAt'),
        ];
    }
}
