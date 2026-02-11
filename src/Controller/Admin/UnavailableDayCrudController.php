<?php

namespace App\Controller\Admin;

use App\Entity\UnavailableDay;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class UnavailableDayCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UnavailableDay::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Jour d\'indisponibilité')
            ->setEntityLabelInPlural('Jours d\'indisponibilité')
            ->setPageTitle(Crud::PAGE_INDEX, 'Jours d\'indisponibilité')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter un jour d\'indisponibilité')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier un jour d\'indisponibilité')
            ->setEntityPermission('ROLE_ADMIN')
            ->setSearchFields(['reason'])
            ->setDefaultSort(['date' => 'ASC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(DateTimeFilter::new('date', 'Date'))
            ->add(TextFilter::new('reason', 'Motif'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            DateField::new('date', 'Date')
                ->setHelp('Jour fermé à la réservation')
                // widgetnatif (calendar), pas de TZ car type DATE
                ->setFormTypeOption('widget', 'single_text')
                // format d'affichage en liste/détail
                ->setFormat('dd/MM/yyyy'),
            TextField::new('reason', 'Motif (optionnel')
                ->setHelp('Ex: Congés, Formation, Exception...')
                ->hideOnIndex(),
        ];
    }
}
