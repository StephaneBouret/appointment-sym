<?php

namespace App\EventDispatcher;

use App\Event\AppointmentCancelledEvent;
use App\Service\SendMailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AppointmentEmailCancelledSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SendMailService $sendMail,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $defaultFrom,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            AppointmentCancelledEvent::NAME => 'onCancelled',
        ];
    }

    public function onCancelled(AppointmentCancelledEvent $event): void
    {
        $appointment   = $event->getAppointment();
        $user          = $appointment->getUser();
        $type          = $appointment->getType();
        $refundPercent = $event->getRefundPercent();
        $refundCents   = $event->getRefundCents();

        $whenParis = $appointment->getStartAt()
            ? $appointment->getStartAt()->setTimezone(new \DateTimeZone('Europe/Paris'))->format('d/m/Y H:i')
            : '-';

        $listUrl = $this->urlGenerator->generate('app_appointment_list', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // Mail UTILISATEUR
        if ($user && $user->getEmail()) {
            $this->sendMail->sendMail(
                null,
                sprintf('Annulation de votre RDV N°%s', (string)($appointment->getNumber() ?? $appointment->getId())),
                $user->getEmail(),
                sprintf(
                    'Votre rendez-vous "%s" du %s a bien été annulé',
                    $type?->getName() ?? 'Rendez-vous',
                    $whenParis
                ),
                'appointment_cancelled_user',
                [
                    'appointment'    => $appointment,
                    'user'           => $user,
                    'type'           => $type,
                    'whenParis'      => $whenParis,
                    'refundPercent'  => $refundPercent,     // ex. 0, 50, 100
                    'refundCents'    => $refundCents,       // ex. 3750
                    'listUrl'        => $listUrl,
                    'policyMessage'  => $event->getPolicyMessage(),
                ]
            );
        }
    }
}
