<?php

namespace App\Event;

use App\Entity\Appointment;

final class AppointmentCancelledEvent
{
    public const NAME = 'appointment.cancelled';

    public function __construct(
        private readonly Appointment $appointment,
        private readonly int $refundPercent, // 0, 50, 100
        private readonly ?int $refundCents, // null si inconnu
        private readonly string $policyTier, // gt48 / 48to24 / lt24
        private readonly string $policyMessage
    ) {}

    public function getAppointment(): Appointment { return $this->appointment; }
    public function getRefundPercent(): int { return $this->refundPercent; }
    public function getRefundCents(): ?int { return $this->refundCents; }
    public function getPolicyTier(): string { return $this->policyTier; }
    public function getPolicyMessage(): string { return $this->policyMessage; }
}
