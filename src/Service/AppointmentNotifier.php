<?php

namespace App\Service;

use App\Entity\Appointment;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class AppointmentNotifier
{
    public function __construct(private MailerInterface $mailer, protected string $defaultFrom) {}

    public function sendConfirmation(Appointment $appointment): void
    {
        $user = $appointment->getUser();
        if (!$user || !$user->getEmail()) return;

        $email = (new TemplatedEmail())
            ->from(new Address($this->defaultFrom, 'L\'Univers des Nombres'))
            ->to(new Address($user->getEmail(), $user->getFullname() ?? $user->getEmail()))
            ->subject('Confirmation de votre rendez-vous')
            ->htmlTemplate('emails/appointment_confirmation.html.twig')
            ->context([
                'appointment' => $appointment,
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}
