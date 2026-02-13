<?php

namespace App\EventDispatcher;

use App\Event\AppointmentRescheduledEvent;
use App\Service\SendMailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AppointmentEmailRescheduledSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SendMailService $sendMail,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $defaultFrom,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            AppointmentRescheduledEvent::NAME => 'onAppointmentRescheduled',
        ];
    }

    public function onAppointmentRescheduled(AppointmentRescheduledEvent $event): void
    {
        $appointment = $event->getAppointment();
        $user        = $appointment->getUser();
        $type        = $appointment->getType();

        $myAppointmentsUrl = $this->urlGenerator->generate('app_appointment_list', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // 1) Mail à l'utilisateur
        if ($user && $user->getEmail()) {
            $this->sendMail->sendMail(
                null,
                sprintf('Votre RDV n°%s est reporté', (string) $appointment->getNumber()),
                $user->getEmail(),
                sprintf(
                    'Confirmation de report de votre rendez-vous "%s" du %s',
                    $type ? $type->getName() : 'RDV',
                    $appointment->getStartAt()?->setTimezone(new \DateTimeZone('Europe/Paris'))?->format('d/m/Y H:i')
                ),
                'appointment_reschedule_user',
                [
                    'appointment'       => $appointment,
                    'user'              => $user,
                    'type'              => $type,
                    'myAppointmentsUrl' => $myAppointmentsUrl,
                    'startAt'           => $appointment->getStartAt(),
                    'endAt'             => $appointment->getEndAt(),
                    'oldStartAt'        => $event->getOldStartAt(),
                    'oldEndAt'          => $event->getOldEndAt(),
                ]
            );
        }
    }
}
