<?php

namespace App\Twig;

use App\Entity\User;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use App\Repository\AppointmentRepository;

final class AppointmentExtension extends AbstractExtension
{
    public function __construct(private AppointmentRepository $repo) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('has_paid_appointments', [$this, 'hasPaidAppointments']),
        ];
    }

    public function hasPaidAppointments(?User $user): bool
    {
        if (!$user) return false;
        return $this->repo->countPaidByUser($user) > 0;
    }
}
