<?php

namespace App\Controller\Traits;

use App\Entity\AppointmentType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

trait ErrorFormTrait
{
    /**
     * Le ErrorFormTrait appelle render('_form.html.twig, [...])
     * On l'étend pour pouvoir passer un tableau $extraVars
     */
    private function renderAppointmentForm(
        FormInterface $form,
        AppointmentType $type,
        ?string $error = null,
        string $routeName = 'app_appointment_ajax_form',
        array $extraVars = []
    ): Response {
        if ($error) {
            $form->addError(new FormError($error));
        }

        return $this->render('appointment/_form.html.twig', array_merge([
            'form' => $form,
            'type' => $type,
            'action' => $this->generateUrl($routeName, ['id' => $type->getId()]),
        ], $extraVars));
    }
}
