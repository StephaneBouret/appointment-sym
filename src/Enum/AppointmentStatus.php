<?php

namespace App\Enum;

enum AppointmentStatus: string
{
    case PENDING = 'pending'; // créneau choisi, paiement pas encore validé
    case CONFIRMED = 'confirmed'; // payé + confirmé automatiquement
    case CANCELED = 'canceled'; // annulé

    public function label(): string
    {
        return match ($this) {
            self::PENDING => "En attente",
            self::CONFIRMED => "RDV confirmé",
            self::CANCELED => "RDV annulé",
        };
    }
}
