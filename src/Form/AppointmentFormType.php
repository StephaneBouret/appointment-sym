<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\EvaluatedPerson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date et heure de début',
                'input' => 'datetime_immutable', // important
                'model_timezone' => 'Europe/Paris', // <<--- crée l'objet en Paris
                'view_timezone' => 'Europe/Paris', // et affiche Paris
                'with_seconds' => false,
            ])
            ->add('evaluatedPerson', EvaluatedPersonType::class, [
                'label' => false,
            ])
        ;

        // Si RDV "Analyse couple", on ajoute le listener
        if ($options['is_couple'] === true) {
            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                [$this, 'onPreSetData']
            );
        }
    }

    public function onPreSetData(PreSetDataEvent $event): void
    {
        /** @var Appointment|null $appointment */
        $appointment = $event->getData();
        $form = $event->getForm();

        if (null === $appointment) {
            return;
        }

        if (!$form->getConfig()->getOption('is_couple')) return;

        // Instancie l'embeddable partenaire si nécessaire
        if (null === $appointment->getPartner()) {
            $appointment->setPartner(new EvaluatedPerson());
        }

        // Ajoute le sous-formulaire "partner"
        $form->add('partner', EvaluatedPersonType::class, [
            'label' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'is_couple' => false,
        ]);
    }
}
