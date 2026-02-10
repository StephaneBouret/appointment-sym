<?php

namespace App\Controller\Admin;

use App\Entity\AppointmentType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Vich\UploaderBundle\Form\Type\VichImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class AppointmentTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AppointmentType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setPageTitle('index', 'Types de RDV :')
            ->setPageTitle('edit', fn(AppointmentType $appointmentType) => (string) $appointmentType->getName())
            ->setPageTitle('detail', fn(AppointmentType $appointmentType) => (string) $appointmentType->getName())
            ->setEntityLabelInSingular('un type de RDV')
            ->setDefaultSort(['id' => 'ASC'])
            ->setPaginatorPageSize(10);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('name', 'Nom'),
            IntegerField::new('duration', 'Durée (minutes)'),
            IntegerField::new('minAge', 'Age min')->hideOnIndex(),
            IntegerField::new('maxAge', 'Age max')->hideOnIndex(),
            MoneyField::new('price', 'Tarif (€)')
                ->setCurrency('EUR'),
            IntegerField::new('participants', 'Participants'),
            AssociationField::new('prerequisite', 'Prérequis')
                ->setFormTypeOption('required', false),
            BooleanField::new('isPack', 'Pack'),
            TextareaField::new('introduction', 'Introduction')->hideOnIndex(),
            TextEditorField::new('description', 'Description')->hideOnIndex(),
            ImageField::new('imageName', 'Image :')
                ->setBasePath('/images/appointments')
                ->setUploadDir('public/images/appointments')
                ->onlyOnIndex(),
            TextField::new('imageFile', 'Fichier image :')
                ->onlyOnForms()
                ->setFormType(VichImageType::class)
                ->setFormTypeOptions(['delete_label' => 'Supprimer l\'image']),
        ];
    }
}
