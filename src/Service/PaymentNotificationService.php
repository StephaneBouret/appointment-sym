<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Event\AppointmentSuccessEvent;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PaymentNotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly LoggerInterface $logger,
    ) {}

    public function deliver(Appointment $appointment): void
    {
        $claimed = $this->em->wrapInTransaction(function () use ($appointment): bool {
            $this->em->refresh($appointment, LockMode::PESSIMISTIC_WRITE);
            $payment = $appointment->getPayment();
            if (!$payment->verifiedAt || !in_array($payment->notificationState, ['pending', 'failed'], true)) {
                return false;
            }
            if ($appointment->getStatus() !== AppointmentStatus::CONFIRMED) {
                $payment->notificationState = 'suppressed';
                return false;
            }
            $payment->notificationState = 'sending';
            $payment->notificationAttemptedAt = new \DateTimeImmutable();
            return true;
        });
        if (!$claimed) {
            return;
        }

        $error = null;
        try {
            $this->dispatcher->dispatch(new AppointmentSuccessEvent($appointment), AppointmentSuccessEvent::NAME);
        } catch (\Throwable $exception) {
            // Do not persist/log SMTP messages: they can contain credentials or personal data.
            $error = $exception::class;
            $this->logger->error('payment_confirmation_notification_failed', [
                'appointment_id' => $appointment->getId(), 'exception_class' => $error,
            ]);
        }

        $this->em->wrapInTransaction(function () use ($appointment, $error): void {
            $this->em->refresh($appointment, LockMode::PESSIMISTIC_WRITE);
            $payment = $appointment->getPayment();
            $payment->notificationState = $error ? 'failed' : 'sent';
            $payment->notificationError = $error;
            if (!$error) {
                $payment->notificationSentAt = new \DateTimeImmutable();
            }
        });
        // A crash during delivery leaves 'sending' visible for manual reconciliation.
        // SMTP cannot guarantee exactly-once delivery after an ambiguous failure.
    }
}
