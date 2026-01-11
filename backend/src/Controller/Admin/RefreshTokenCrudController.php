<?php

namespace App\Controller\Admin;

use App\Entity\RefreshToken;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RefreshTokenCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RefreshToken::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('refreshToken'),
            TextField::new('username'),
            DateTimeField::new('valid'),
        ];
    }
}
