<?php

namespace App\Event;

use App\Entity\Appointment;

final class AppointmentRescheduledEvent
{
    public const NAME = 'appointment.rescheduled';

    public function __construct(
        private readonly Appointment $appointment,
        private readonly \DateTimeImmutable $oldStartAt,
        private readonly \DateTimeImmutable $oldEndAt,
    ) {}

    public function getAppointment(): Appointment { return $this->appointment; }
    public function getOldStartAt(): \DateTimeImmutable { return $this->oldStartAt; }
    public function getOldEndAt(): \DateTimeImmutable { return $this->oldEndAt; }
}
