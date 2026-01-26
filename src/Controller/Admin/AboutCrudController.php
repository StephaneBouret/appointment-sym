<?php

namespace App\Controller\Admin;

use App\Entity\About;
use App\Form\LinkFormType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class AboutCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return About::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setPageTitle('index', 'Formateurs :')
            ->setPageTitle('new', 'Créer un formateur')
            ->setPageTitle('edit', fn(About $about) => (string) $about->getFullname())
            ->setPageTitle('detail', fn(About $about) => (string) $about->getFullname())
            ->setEntityLabelInSingular('un formateur')
            ->setDefaultSort(['id' => 'ASC'])
            ->setPaginatorPageSize(10);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('firstname', 'Prénom du formateur'),
            TextField::new('lastname', 'Nom du formateur'),
            TextField::new('imageFile', 'Fichier image :')
                ->setFormType(VichImageType::class)
                ->setTranslationParameters(['form.label.delete' => 'Supprimer l\'image'])
                ->hideOnIndex(),
            ImageField::new('imageName', 'Image')
                ->setBasePath('/images/about')
                ->onlyOnIndex(),
            TextEditorField::new('description', 'Détail du formateur')
                ->hideOnIndex(),
            CollectionField::new('links', 'Réseaux sociaux')
                ->setEntryType(LinkFormType::class)
                ->setFormTypeOption('by_reference', false)
                ->hideOnIndex(),
        ];
    }
}
