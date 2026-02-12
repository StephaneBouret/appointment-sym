<?php

namespace App\Event;

use App\Entity\Appointment;

class AppointmentSuccessEvent
{
    public const NAME = 'appointment.success';

    public function __construct(private readonly Appointment $appointment) {}

    public function getAppointment(): Appointment
    {
        return $this->appointment;
    }
}
