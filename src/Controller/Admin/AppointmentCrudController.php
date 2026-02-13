<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\AppointmentType;
use App\Enum\AppointmentStatus;
use App\Form\EvaluatedPersonType;
use App\Repository\AppointmentRepository;
use App\Service\AppointmentExportService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NullFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Vich\UploaderBundle\Form\Type\VichFileType;

class AppointmentCrudController extends AbstractCrudController
{
    public function __construct(protected AppointmentRepository $appointmentRepository, protected AdminUrlGenerator $adminUrl) {}

    public static function getEntityFqcn(): string
    {
        return Appointment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Rendez-vous')
            ->setEntityLabelInPlural('Rendez-vous')
            ->setDefaultSort(['startAt' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->setTimezone('Europe/Paris')
            ->setPageTitle(Crud::PAGE_INDEX, 'Rendez-vous')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un rendez-vous')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le rendez-vous')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du rendez-vous');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(DateTimeFilter::new('startAt', 'Date/heure'))
            ->add(EntityFilter::new('user', 'Client'))
            ->add(EntityFilter::new('type', 'Type'))
            ->add(ChoiceFilter::new('status', 'Statut')->setChoices([
                'En attente'   => AppointmentStatus::PENDING->value,
                'RDV confirmé' => AppointmentStatus::CONFIRMED->value,
                'RDV annulé'   => AppointmentStatus::CANCELED->value,
            ]))
            ->add(
                NullFilter::new('number', 'Numéro renseigné')
                    ->setChoiceLabels('Sans numéro', 'Avec numéro')
            );
    }

    public function configureActions(Actions $actions): Actions
    {
        $hasUnsent = $this->appointmentRepository->count(['isSent' => false]) > 0;

        $exportCsv = Action::new('exportCsv', 'Exporter CSV', 'fa fa-file-csv')
            ->linkToCrudAction('exportCsv')
            ->displayIf(fn() => $hasUnsent)
            ->addCssClass('btn btn-success')
            ->createAsGlobalAction();

        $exportAllCsv = Action::new('exportCsvAll', 'Réexporter CSV (tout)', 'fa fa-file-export')
            ->linkToCrudAction('exportCsvAll')
            ->addCssClass('btn btn-outline-primary')
            ->createAsGlobalAction();

        $exportIcs = Action::new('exportIcs', 'Exporter ICS', 'fa fa-calendar')
            ->linkToCrudAction('exportIcs')
            ->displayIf(fn() => $hasUnsent)
            ->addCssClass('btn btn-success')
            ->createAsGlobalAction();

        $exportAllIcs = Action::new('exportIcsAll', 'Réexporter ICS (tout)', 'fa fa-calendar')
            ->linkToCrudAction('exportIcsAll')
            ->addCssClass('btn btn-outline-primary')
            ->createAsGlobalAction();

        $generatePdf = Action::new('generatePdf', 'Générer PDF')
            ->linkToUrl(function (Appointment $appointment) {
                return $this->adminUrl
                    ->setRoute('app_appointment_pdf_admin', ['id' => $appointment->getId()])
                    ->generateUrl();
            })
            ->setIcon('fa fa-file-pdf')
            ->addCssClass('btn btn-secondary')
            ->displayIf(fn(Appointment $appointment) => $appointment->getStatus() === AppointmentStatus::CONFIRMED);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $exportCsv)
            ->add(Crud::PAGE_INDEX, $exportAllCsv)
            ->add(Crud::PAGE_INDEX, $exportIcs)
            ->add(Crud::PAGE_INDEX, $exportAllIcs)
            ->add(Crud::PAGE_INDEX, $generatePdf)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_EDIT) {
            yield FormField::addPanel('Ressources du rendez-vous');

            yield TextField::new('visioUrl', 'Lien de visioconférence')
                ->setRequired(false)
                ->setFormTypeOption('attr', [
                    'placeholder' => 'https://dicord.gg/... ou lien Teams'
                ]);

            yield Field::new('pdfFile', 'Support PDF')
                ->setFormType(VichFileType::class)
                ->setFormTypeOptions([
                    'required' => false,
                    'allow_delete' => true,
                    'delete_label' => 'Supprimer le fichier existant',
                    'download_uri' => true,
                    'download_label' => 'Télécharger le fichier actuel',
                    'asset_helper' => true,
                ])
                ->onlyOnForms();

            return;
        }

        yield IdField::new('id')->onlyOnIndex();

        yield AssociationField::new('user', 'Client')
            ->setRequired(true)
            ->setFormTypeOptions(['placeholder' => '— choisir —']);

        // Affiche le Type + quelques infos utiles (durée/prix/participants) via help
        yield AssociationField::new('type', 'Type')
            ->setRequired(true)
            ->setFormTypeOptions(['placeholder' => '— choisir —'])
            ->setHelp('La durée et l\'heure de fin seront calculées automatiquement.');

        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'En attente' => AppointmentStatus::PENDING,
                'RDV confirmé'  => AppointmentStatus::CONFIRMED,
                'RDV annulé'    => AppointmentStatus::CANCELED,
            ])
            ->renderAsBadges([
                AppointmentStatus::PENDING->value => 'warning',
                AppointmentStatus::CONFIRMED->value => 'success',
                AppointmentStatus::CANCELED->value  => 'danger',
            ])
            ->formatValue(fn($value) => $value instanceof AppointmentStatus ? $value->label() : $value);

        yield TextField::new('visioUrl', 'Lien de visioconférence')
            ->setRequired(false)
            ->setFormTypeOption('attr', [
                'placeholder' => 'https://dicord.gg/... ou lien Teams'
            ])
            ->onlyOnDetail();

        yield TextField::new('pdfName', 'Support PDF')
            ->onlyOnIndex();

        yield TextField::new('number', 'Numéro')->onlyOnIndex();

        // Planification (Europe/Paris + immutables)
        yield FormField::addPanel('Planification')->setIcon('fa fa-calendar');
        yield DateTimeField::new('startAt', 'Début')
            ->setFormTypeOption('widget', 'single-text')
            ->setFormTypeOption('model_timezone', 'UTC')            // ⬅️ était Europe/Paris
            ->setFormTypeOption('view_timezone', 'Europe/Paris');
        yield DateTimeField::new('endAt', 'Fin')->onlyOnIndex()
            ->setFormTypeOption('model_timezone', 'UTC')            // ⬅️ idem
            ->setFormTypeOption('view_timezone', 'Europe/Paris');
        yield DateTimeField::new('endAt', 'Fin')->onlyOnDetail()
            ->setFormTypeOption('model_timezone', 'UTC')            // ⬅️ idem
            ->setFormTypeOption('view_timezone', 'Europe/Paris');

        // Personne évaluée (embeddable)
        yield FormField::addPanel('Personne évaluée')->setIcon('fa fa-user');
        yield Field::new('evaluatedPerson', false)
            ->setFormType(EvaluatedPersonType::class)
            ->onlyOnForms();

        yield TextField::new('evaluatedPerson.firstname', 'Prénoms')->onlyOnDetail();
        yield TextField::new('evaluatedPerson.lastname', 'Noms')->onlyOnDetail();
        yield TextField::new('evaluatedPerson.patronyms', 'Patronymes')->onlyOnDetail();
        yield DateField::new('evaluatedPerson.birthdate', 'Naissance')->onlyOnDetail();

        // ---- Affichage du partenaire dans la vue détail ----
        if ($pageName === Crud::PAGE_DETAIL) {
            /** @var Appointment|null $current */
            $current = $this->getContext()?->getEntity()?->getInstance();
            if ($current && $current->getType()) {
                $type = $current->getType();
                $isCouple = $type->getSlug() === 'analyse-couple' || $type->getId() === 6;

                if ($isCouple) {
                    yield FormField::addPanel('Partenaire')->setIcon('fa fa-user-friends')->onlyOnDetail();
                    yield TextField::new('partner.firstname', 'Prénoms (partenaire)')->onlyOnDetail();
                    yield TextField::new('partner.lastname', 'Noms (partenaire)')->onlyOnDetail();
                    yield TextField::new('partner.patronyms', 'Patronymes (partenaire)')->onlyOnDetail();
                    yield DateField::new('partner.birthdate', 'Naissance (partenaire)')->onlyOnDetail();
                }
            }
        }

        // Partenaire (si participants >= 2). Le champ reste optionnel en base
        yield FormField::addPanel('Partenaire (si applicable)')->setIcon('fa fa-user-friends')->onlyOnForms();
        yield Field::new('partner', false)
            ->setFormType(EvaluatedPersonType::class)
            ->onlyOnForms()
            ->setHelp('Renseigner si le type de RDV concerne plusieurs participants.');

        // Meta
        yield FormField::addPanel('Historique')->setIcon('fa fa-history')->onlyOnDetail();
        yield DateTimeField::new('createdAt', 'Créé le')->onlyOnDetail()
            ->setFormTypeOption('model_timezone', 'UTC')            // ⬅️ idem
            ->setFormTypeOption('view_timezone', 'Europe/Paris');

        yield DateTimeField::new('updatedAt', 'MAJ le')->onlyOnDetail()
            ->setFormTypeOption('model_timezone', 'UTC')            // ⬅️ idem
            ->setFormTypeOption('view_timezone', 'Europe/Paris');

        yield MoneyField::new('type.price', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents()
            ->onlyOnIndex();

        yield IntegerField::new('type.participants', 'Participants')->onlyOnIndex();
    }

    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if ($entityInstance instanceof Appointment) {
            $this->syncEndAtAndTimestamps($entityInstance);
        }
        parent::persistEntity($em, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if ($entityInstance instanceof Appointment) {
            $this->syncEndAtAndTimestamps($entityInstance);
        }
        parent::updateEntity($em, $entityInstance);
    }

    /**
     * Calcule endAt depuis startAt + type.duration (minutes),
     * et met à jour updatedAt systématiquement.
     */
    private function syncEndAtAndTimestamps(Appointment $appointment): void
    {
        $appointment->setUpdatedAt(new \DateTimeImmutable());

        $type = $appointment->getType();
        $startAt = $appointment->getStartAt();

        if ($type instanceof AppointmentType && $startAt instanceof \DateTimeImmutable) {
            $duration = $type->getDuration(); // minutes (int|null)
            if (is_int($duration) && $duration >= 0) {
                $appointment->setEndAt($startAt->modify('+' . $duration . ' minutes'));
                return;
            }
        }

        // fallback : si pas de durée, évite null
        if (!$appointment->getEndAt() && $startAt) {
            $appointment->setEndAt($startAt);
        }
    }

    public function exportCsv(AppointmentExportService $exporter): StreamedResponse
    {
        $list = $this->appointmentRepository->findForCalendarExport(includePending: false, onlyNotSent: true);
        $resp = $exporter->streamCsv($list, 'confirmed');
        $this->appointmentRepository->markAsSent($list);
        return $resp;
    }

    public function exportCsvAll(AppointmentExportService $exporter): StreamedResponse
    {
        $list = $this->appointmentRepository->findForCalendarExport(includePending: false, onlyNotSent: false);
        return $exporter->streamCsv($list, 'all'); // Pas de markAsSent
    }

    public function exportIcs(AppointmentExportService $exporter): StreamedResponse
    {
        $list = $this->appointmentRepository->findForCalendarExport(includePending: false, onlyNotSent: true);
        $resp = $exporter->streamIcs($list, 'confirmed');
        $this->appointmentRepository->markAsSent($list);
        return $resp;
    }

    public function exportIcsAll(AppointmentExportService $exporter): StreamedResponse
    {
        $list = $this->appointmentRepository->findForCalendarExport(includePending: false, onlyNotSent: false);
        return $exporter->streamIcs($list, 'all'); // pas de markAsSent
    }
}
