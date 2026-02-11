<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\EvaluatedPerson;
use App\Form\EvaluatedPersonType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GroupSequence;

final class AppointmentEditPersonsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('evaluatedPerson', EvaluatedPersonType::class, [
            'label' => false,
        ]);

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

        if (null === $appointment->getPartner()) {
            $appointment->setPartner(new EvaluatedPerson());
        }

        $form->add('partner', EvaluatedPersonType::class, [
            'label' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'is_couple'  => false,
            'validation_groups' => function (FormInterface $form) {
                /** @var Appointment|null $data */
                $data = $form->getData();
                if (!$data) return ['Default'];

                $type = $data->getType();
                $isCouple = $type && (method_exists($type, 'isCouple')
                    ? $type->isCouple()
                    : ((int)$type->getParticipants() === 2));

                return $isCouple ? ['Default', 'couple'] : ['Default'];
            },
        ]);
    }
}
