<?php

namespace App\Controller\Admin;

use App\Entity\Unavailability;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;

class UnavailabilityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Unavailability::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Indisponibilité horaire')
            ->setEntityLabelInPlural('Indisponibilités horaires')
            ->setPageTitle(Crud::PAGE_INDEX, 'Indisponibilités (plages horaires)')
            ->setPageTitle(Crud::PAGE_NEW, 'Nouvelle indisponibilité')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier l\'indisponibilité')
            ->setEntityPermission('ROLE_ADMIN')
            ->setSearchFields(['reason'])
            ->setDefaultSort(['startAt' => 'ASC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(DateTimeFilter::new('startAt', 'Début'))
            ->add(DateTimeFilter::new('endAt', 'Fin'))
            ->add(TextFilter::new('reason', 'Motif'));
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        // Affichage en Europe/paris dans les listings
        $displayTz = 'Europe/Paris';

        return [
            IdField::new('id')->onlyOnIndex(),
            DateTimeField::new('startAt', 'Début')
                ->setHelp('Date & Heure de début')
                // Saisie : vue Paris / modèle UTC
                ->setFormTypeOption('widget', 'single_text')
                ->setFormTypeOption('input', 'datetime_immutable')
                ->setFormTypeOption('view_timezone', $displayTz)
                ->setFormTypeOption('model_timezone', 'UTC')
                // Affichage index/show
                ->setTimezone($displayTz),
            DateTimeField::new('endAt', 'Fin')
                ->setHelp('Date & Heure de fin')
                ->setFormTypeOption('widget', 'single_text')
                ->setFormTypeOption('input', 'datetime_immutable')
                ->setFormTypeOption('view_timezone', $displayTz)
                ->setFormTypeOption('model_timezone', 'UTC')
                ->setTimezone($displayTz),
            BooleanField::new('allDay', 'Journée entière')
                ->setHelp('Si coché, la plage sera normalisée à 00.00-23:59 pour le jour concerné'),
            TextField::new('reason', 'Motif (optionnel)')->hideOnIndex(),
        ];
    }

    /**
     * Normalise si "Journée entière" est cochée : 00:00-23:59 sur la date de startAt
     * La journée est déterminée en Europe/Paris ; le type Doctrine persiste les instants en UTC.
     */
    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        $this->normalizeAllDayIfNeeded($entityInstance);
        parent::persistEntity($em, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        $this->normalizeAllDayIfNeeded($entityInstance);
        parent::updateEntity($em, $entityInstance);
    }

    private function normalizeAllDayIfNeeded($entityInstance): void
    {
        if (!$entityInstance instanceof Unavailability) {
            return;
        }
        if (!$entityInstance->isAllDay()) {
            return;
        }
        $startAt = $entityInstance->getStartAt();
        if (!$startAt) {
            return;
        }

        $paris = new \DateTimeZone('Europe/Paris');
        $dayParis = $startAt->setTimezone($paris); // on "voit" le jour réel sur Paris

        // 00:00:00 et 23:59:59 (Paris)
        $startParis = $dayParis->setTime(0, 0, 0);
        $endParis = $dayParis->setTime(23, 59, 59);

        // Doctrine ne remplace pas les objets gérés lors du flush. Conserver aussi en mémoire
        // le modèle UTC attendu par le formulaire, sans changer les instants Paris calculés.
        $utc = new \DateTimeZone('UTC');
        $entityInstance->setStartAt($startParis->setTimezone($utc));
        $entityInstance->setEndAt($endParis->setTimezone($utc));
    }
}
