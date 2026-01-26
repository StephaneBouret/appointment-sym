<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\AvatarFormType;
use libphonenumber\PhoneNumberFormat;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use Misd\PhoneNumberBundle\Templating\Helper\PhoneNumberHelper;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class UserCrudController extends AbstractCrudController
{
    public function __construct(protected PhoneNumberHelper $phoneNumberHelper) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            // ->setEntityPermission('ROLE_ADMIN')
            ->setPageTitle('index', 'Utilisateurs :')
            ->setPageTitle('new', 'Créer un utilisateur')
            ->setPageTitle('edit', fn(User $user) => (string) $user->getFullname())
            ->setPageTitle('detail', fn(User $user) => (string) $user->getFullname())
            ->setEntityLabelInSingular('un utilisateur')
            ->setDefaultSort(['id' => 'ASC'])
            ->setPaginatorPageSize(10);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        $actions->disable(Action::NEW);
        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            FormField::addFieldset('Détails de l\'utilisateur'),
            TextField::new('firstname', 'Prénom :')
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Prénom de l\'utilisateur']])
                ->setColumns(6),
            TextField::new('lastname', 'Nom :')
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Nom de l\'utilisateur']])
                ->setColumns(6),
            EmailField::new('email', 'Email :')
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Email de l\'utilisateur']]),
            TextField::new('adress', 'Adresse :')
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Adresse de l\'utilisateur']])
                ->setColumns(6)
                ->hideOnIndex(),
            TextField::new('postalCode', 'Code postal :')
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Code postal de l\'utilisateur']])
                ->setColumns(6)
                ->hideOnIndex(),
            TextField::new('city', 'Ville :')
                ->setFormTypeOptions(['attr' => ['placeholder' => 'Ville de l\'utilisateur']])
                ->setColumns(6)
                ->hideOnIndex(),
            TextField::new('phone', 'Téléphone')
                ->setFormType(PhoneNumberType::class)
                ->setFormTypeOptions([
                    'default_region' => 'FR',
                    'format' => PhoneNumberFormat::NATIONAL,
                    'attr' => ['placeholder' => 'Téléphone de l\'utilisateur']
                ])
                ->setColumns(6)
                ->onlyOnForms(),
            TextField::new('phone', 'Téléphone')
                ->formatValue(function ($value, $entity) {
                    $value = $entity->getPhone();
                    $formattedValue = $this->phoneNumberHelper->format($value, 2);
                    return $formattedValue;
                })
                ->onlyOnIndex(),
            ImageField::new('avatar.imageName', 'Avatar')
                ->setBasePath('images/avatars')
                ->setUploadDir('public/images/avatars')
                ->onlyOnIndex()
                ->setUploadedFileNamePattern('[randomhash].[extension]')
                ->setRequired(false),
            TextField::new('avatar', 'Avatar :')
                ->setFormType(AvatarFormType::class)
                ->setTranslationParameters(['form.label.delete' => 'Supprimer l\'image'])
                ->hideOnIndex(),
            FormField::addFieldset('Rôles de l\'utilisateur'),
            ChoiceField::new('roles')
                ->setChoices([
                    'Administrateur' => 'ROLE_ADMIN',
                    'Invité' => 'ROLE_GUEST',
                    // Pas d'affichage de ROLE_USER afin d'éviter qu'il soit décoché
                ])
                ->allowMultipleChoices()
                ->renderExpanded()
                ->renderAsBadges()
                ->hideOnIndex(),
            ChoiceField::new('roles')
                ->setChoices([
                    'Administrateur' => 'ROLE_ADMIN',
                    'Invité' => 'ROLE_GUEST',
                    'Utilisateur' => 'ROLE_USER',
                ])
                ->renderAsBadges()
                ->setChoices(array_flip([
                    'ROLE_ADMIN' => 'Administrateur',
                    'ROLE_GUEST' => 'Invité',
                    'ROLE_USER' => 'Utilisateur',
                ])) // Inverse clés/valeurs afin d'éviter le formatValue()
                ->onlyOnIndex(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add(BooleanFilter::new('isVerified'));
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $roles = $entityInstance->getRoles();
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
            $entityInstance->setRoles($roles);
        }
        $entityInstance->setFirstname(ucfirst($entityInstance->getFirstname()))
            ->setLastname(strtoupper($entityInstance->getLastname()));
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $roles = $entityInstance->getRoles();
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
            $entityInstance->setRoles($roles);
        }
        $entityInstance->setFirstname(ucfirst($entityInstance->getFirstname()))
                    ->setLastname(strtoupper($entityInstance->getLastname()));
        parent::persistEntity($entityManager, $entityInstance);
    }
}
