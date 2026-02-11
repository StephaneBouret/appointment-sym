<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

class AppointmentValidator
{
    public function __construct(private AppointmentRepository $appointmentRepository) {}

    public function validate(Appointment $appointment, User $user, FormInterface $form): void
    {
        $type = $appointment->getType();
        $birthdate = $appointment->getEvaluatedPerson()->getBirthdate();
        $age = $birthdate ? $birthdate->diff(new \DateTimeImmutable())->y : null;

        // Vérification âge mini
        if ($type->getMinAge() !== null && $age < $type->getMinAge()) {
            $form->get('evaluatedPerson')->get('birthdate')->addError(new FormError("L'âge minimum pour ce rendez-vous est de {$type->getMinAge()} ans."));
        }

        // Vérification âge maxi
        if ($type->getMaxAge() !== null && $age > $type->getMaxAge()) {
            $form->get('evaluatedPerson')->get('birthdate')->addError(new FormError("L'âge maximum pour ce rendez-vous est de {$type->getMaxAge()} ans."));
        }

        // Vérification prérequis
        if ($type->getPrerequisite()) {
            $hasPrerequisite = $this->appointmentRepository->hasUserCompletedType($user, $type->getPrerequisite());
            if (!$hasPrerequisite) {
                $form->addError(new FormError("Pour réserver ce rendez-vous, vous devez d'abord effectuer : " . $type->getPrerequisite()->getName()));
            }
        }
    }
}
