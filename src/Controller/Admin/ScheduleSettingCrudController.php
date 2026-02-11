<?php

namespace App\Controller\Admin;

use App\Entity\ScheduleSetting;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ScheduleSettingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ScheduleSetting::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular("Réglage")
            ->setEntityLabelInPlural("Réglages")
            ->setPageTitle(Crud::PAGE_INDEX, 'Paramètres de planning')
            ->setPageTitle(Crud::PAGE_NEW, 'Nouveau paramètre')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le paramètre')
            ->setEntityPermission('ROLE_ADMIN')
            ->setSearchFields(['setting_key', 'value']);
    }

    public function configureActions(Actions $actions): Actions
    {
        // On laisse tout (INDEX, NEW, EDIT, DELETE)
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        // Champ clé
        $keyField = TextField::new('setting_key', 'Clé')
            ->setHelp(
                "Clé supportées :
                <code>morning_start</code> (HH:MM),
                <code>morning_end</code> (HH:MM),
                <code>afternoon_start</code> (HH:MM),
                <code>afternoon_end</code> (HH:MM),
                <code>open_days</code> (ex: 1,2,3,4,5)
                <code>slot_buffer_minutes</code> (entier, ex: 15)
                <code>fixed_slots</code> (0 ou 1)
                <code>opening_delay_hours</code> (entier, ex: 48 pour J+2, 72 pour J+3)
                <code>reschedule_min_notice_hours</code> (préavis minimal de report en heures, défaut 24)."
            )
            ->setFormTypeOptions([
                'attr' => [
                    'placeholder' => 'ex: morning_start',
                ],
            ]);

        if ($pageName === Crud::PAGE_EDIT) {
            // On vérouille la clé en édition afin d'éviter les bourdes
            $keyField = $keyField->setDisabled();
        }

        yield $keyField;

        // Valeur adaptée par défaut
        $valueField = TextField::new('value', 'Valeur')
            ->setHelp(
                "Heures : format <code>HH:MM</code> (ex: 09:00).<br>
                Jours <code>open_days</code> : liste de 1 à 7 séparés par des virgules (ex: 1,2,3,4,5 ; Lundi = 1).<br>
                Entier pour <code>slot_buffer_minutes</code> (ex: 15), pour <code>fixed_slots</code> (0 ou 1) et <code>opening_delay_hours</code>.
                Pour <code>reschedule_min_notice_hours</code>, mettre 24 (ou plus)."
            )
            ->setFormTypeOptions([
                'attr' => [
                    'placeholder' => 'ex: 09:00 ou 1,2,3,4,5 ou 15 ou 48 ou 24',
                    // pattern généraliste : HH:MM OU liste 1..7 séparés par virgules OU entier
                    'pattern' => '^(?:\d{2}:\d{2}|[1-7](?:,[1-7]){0,6}|\d+)$',
                    'title' => 'Heure au format HH:MM (ex : 09:00) ou jours (ex : 1,2,3,4,5) ou entier (minutes)',
                    'inputmode' => 'text',
                ],
            ]);

        // Si on est en édition ET que la clé est fixed_slots, on force 0|1
        if ($pageName === Crud::PAGE_EDIT) {
            $instance = $this->getContext()->getEntity()->getInstance();
            if ($instance instanceof ScheduleSetting) {
                if ($instance->getSettingKey() === 'fixed_slots') {
                    // fixed_slots => seulement 0 ou 1
                    $valueField = $valueField
                        ->setHelp("<code>fixed_slots</code> : valeur autorisée <strong>0</strong> (désactivé) ou <strong>1</strong> (activé)")
                        ->setFormTypeOptions([
                            'attr' => [
                                'placeholder' => '0 ou 1',
                                'pattern' => '^(0|1)$',
                                'title' => 'Uniquement 0 ou 1',
                            ],
                        ]);
                }

                if ($instance->getSettingKey() === 'opening_delay_hours') {
                    $valueField = $valueField
                        ->setHelp("<code>opening_delay_hours</code> : entier ≥ 0 (48 = J+2, 72 = J+3)")
                        ->setFormTypeOptions([
                            'attr' => [
                                'placeholder' => 'ex: 48',
                                'pattern' => '^\d+$',
                                'title' => 'Entier en heures (≥ 0)',
                            ],
                        ]);
                }
            }
        }

        yield $valueField;
    }

    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if ($entityInstance instanceof ScheduleSetting) {
            $this->applyDefaults($entityInstance);
        }
        parent::persistEntity($em, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if ($entityInstance instanceof ScheduleSetting) {
            $this->applyDefaults($entityInstance);
        }
        parent::updateEntity($em, $entityInstance);
    }

    private function applyDefaults(ScheduleSetting $s): void
    {
        if (strtolower((string) $s->getSettingKey() === 'reschedule_min_notice_hours')) {
            $val = trim((string) $s->getValue());
            if ($val === '') {
                $s->setValue('24');
            }
        }
    }
}
