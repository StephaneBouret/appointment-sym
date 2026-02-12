<?php

namespace App\EventDispatcher;

use App\Event\AppointmentSuccessEvent;
use App\Service\SendMailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AppointmentEmailSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SendMailService $sendMail,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $defaultFrom,
    ) {}
    public static function getSubscribedEvents(): array
    {
        return [
            AppointmentSuccessEvent::NAME => 'onAppointmentSuccess',
        ];
    }

    public function onAppointmentSuccess(AppointmentSuccessEvent $event): void
    {
        $appointment = $event->getAppointment();
        $user        = $appointment->getUser();
        $type        = $appointment->getType();

        // Lien pratique
        $myAppointmentsUrl = $this->urlGenerator->generate('app_appointment_list', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // 1) Mail à l'utilisateur
        if ($user && $user->getEmail()) {
            $this->sendMail->sendMail(
                null,
                sprintf('Votre RDV n°%s est confirmé', (string) $appointment->getNumber()),
                $user->getEmail(),
                sprintf(
                    'Votre rendez-vous "%s" du %s est confirmé',
                    $type ? $type->getName() : 'RDV',
                    $appointment->getStartAt()?->setTimezone(new \DateTimeZone('Europe/Paris'))?->format('d/m/Y H:i')
                ),
                'appointment_success_user',
                [
                    'appointment'       => $appointment,
                    'user'              => $user,
                    'type'              => $type,
                    'myAppointmentsUrl' => $myAppointmentsUrl,
                ]
            );
        }

        // A décommenter lors de la prod
        // 2) Mail à l'ADMIN (toujours informé)
        // if (!empty($this->defaultFrom)) {
        //     $this->sendMail->sendMail(
        //         null,
        //         sprintf('✅ RDV payé — #%s — %s', (string) $appointment->getNumber(), $user?->getEmail() ?? 'user inconnu'),
        //         $this->defaultFrom,
        //         'Un rendez-vous vient d\'être payé et confirmé',
        //         'appointment_success_admin',
        //         [
        //             'appointment' => $appointment,
        //             'user'        => $user,
        //             'type'        => $type,
        //         ]
        //     );
        // }
    }
}
