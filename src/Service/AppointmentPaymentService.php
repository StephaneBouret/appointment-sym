<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;
use App\Stripe\StripeService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\PaymentIntent;

/** The sole automatic paid-confirmation path, shared by browser return and webhook. */
class AppointmentPaymentService
{
    public const PENDING_MINUTES = 30;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StripeService $stripe,
        private readonly PurchaseNumberGenerator $numbers,
        private readonly PaymentNotificationService $notifications,
        private readonly LoggerInterface $logger,
    ) {}

    public function acceptTerms(Appointment $appointment): bool
    {
        return $this->locked($appointment, function () use ($appointment): bool {
            if (!$this->canPay($appointment)) {
                return false;
            }
            $payment = $appointment->getPayment();
            if (!$payment->acceptedAt) {
                $amount = $appointment->getType()?->getPrice();
                // No automatic free-payment flow existed. Leave manual/admin appointments alone.
                if ($amount === null || $amount <= 0) {
                    return false;
                }
                $payment->amount = $amount;
                $payment->currency = 'eur';
                $payment->attemptKey = bin2hex(random_bytes(24));
                $payment->acceptedAt = new \DateTimeImmutable();
                $payment->expiresAt = $appointment->getCreatedAt()->modify('+'.self::PENDING_MINUTES.' minutes');
            }
            return true;
        });
    }

    public function getOrCreateIntent(Appointment $appointment): ?PaymentIntent
    {
        return $this->locked($appointment, function () use ($appointment): ?PaymentIntent {
            $payment = $appointment->getPayment();
            if (!$payment->acceptedAt || !$this->canPay($appointment)) {
                return null;
            }
            if ($payment->intentId) {
                return $this->stripe->retrievePaymentIntent($payment->intentId);
            }
            $intent = $this->stripe->createAppointmentIntent($appointment);
            $payment->intentId = $intent->id;
            return $intent;
        });
    }

    public function confirmReturn(Appointment $appointment): string
    {
        // Never read a payment identifier, status or amount from request parameters.
        $this->em->refresh($appointment);
        $id = $appointment->getPayment()->intentId;
        if (!$id) {
            return 'unverified';
        }
        $result = $this->confirm($appointment, $this->stripe->retrievePaymentIntent($id));
        // Browser returns may deliver after commit. Webhooks only persist the outbox.
        if ($result === 'confirmed') {
            $this->notifications->deliver($appointment);
        }
        return $result;
    }

    /** The caller must have verified Stripe's signature on the raw request. */
    public function confirmWebhook(PaymentIntent $intent): string
    {
        $appointment = $this->em->getRepository(Appointment::class)->findOneBy(['payment.intentId' => $intent->id]);
        if (!$appointment) {
            // Includes pre-deployment intents: metadata alone must never establish the association.
            $this->logger->error('stripe_payment_unassociated', ['payment_intent' => $intent->id]);
            return 'unassociated';
        }
        return $this->confirm($appointment, $intent);
    }

    private function confirm(Appointment $appointment, PaymentIntent $intent): string
    {
        return $this->locked($appointment, function () use ($appointment, $intent): string {
            $payment = $appointment->getPayment();
            if (!$payment->acceptedAt || !$payment->attemptKey || $payment->intentId !== $intent->id
                || ($intent->metadata['kind'] ?? null) !== 'appointment'
                || ($intent->metadata['appointment_id'] ?? null) !== (string) $appointment->getId()
                || ($intent->metadata['attempt_key'] ?? null) !== $payment->attemptKey) {
                return $this->issue($appointment, 'association_mismatch');
            }
            if ($intent->status !== 'succeeded') {
                return 'unpaid';
            }
            if ($payment->amount === null || $payment->amount <= 0
                || $intent->amount !== $payment->amount || $intent->amount_received !== $payment->amount
                || $intent->currency !== $payment->currency) {
                return $this->issue($appointment, 'amount_or_currency_mismatch');
            }
            if ($appointment->getStatus() === AppointmentStatus::CONFIRMED) {
                return $payment->verifiedAt ? 'confirmed' : $this->issue($appointment, 'manual_confirmation_review');
            }
            // Only automatic confirmation creates both a number and a notification.
            // verifiedAt alone also exists for a first late payment: it is insufficient.
            if ($appointment->getStatus() === AppointmentStatus::CANCELED
                && $payment->verifiedAt && $appointment->getNumber() !== null
                && $payment->notificationState !== null) {
                return 'confirmed_then_canceled';
            }
            if (!$this->canPay($appointment)) {
                // Payment proof is retained, but never resurrect a released slot.
                $payment->verifiedAt ??= new \DateTimeImmutable();
                if ($appointment->getStatus() === AppointmentStatus::PENDING) {
                    $appointment->setStatus(AppointmentStatus::CANCELED);
                }
                return $this->issue($appointment, 'late_payment');
            }
            $appointment->setStatus(AppointmentStatus::CONFIRMED)
                ->setNumber($appointment->getNumber() ?? $this->numbers->generate())
                ->setUpdatedAt(new \DateTimeImmutable());
            $payment->verifiedAt = new \DateTimeImmutable();
            $payment->issue = null;
            $payment->notificationState = 'pending';
            return 'confirmed';
        });
    }

    public function expire(Appointment $appointment, \DateTimeImmutable $threshold, ?\DateTimeImmutable $now = null, bool $dryRun = false): bool
    {
        $now ??= new \DateTimeImmutable();
        return $this->locked($appointment, function () use ($appointment, $threshold, $now, $dryRun): bool {
            $expiry = $appointment->getPayment()->expiresAt;
            // --minutes is only the legacy fallback when no deadline was persisted.
            $expired = $expiry !== null ? $expiry <= $now : $appointment->getCreatedAt() <= $threshold;
            if ($appointment->getStatus() !== AppointmentStatus::PENDING || !$expired) {
                return false;
            }
            if (!$dryRun) {
                $appointment->setStatus(AppointmentStatus::CANCELED)->setUpdatedAt($now);
            }
            return true;
        });
    }

    private function canPay(Appointment $appointment): bool
    {
        $expiry = $appointment->getPayment()->expiresAt
            ?? $appointment->getCreatedAt()?->modify('+'.self::PENDING_MINUTES.' minutes');
        return $appointment->getStatus() === AppointmentStatus::PENDING
            && $expiry !== null && $expiry > new \DateTimeImmutable();
    }

    private function issue(Appointment $appointment, string $code): string
    {
        $appointment->getPayment()->issue = $code;
        $this->logger->error('stripe_payment_review_required', [
            'appointment_id' => $appointment->getId(),
            'payment_intent' => $appointment->getPayment()->intentId,
            'reason' => $code,
        ]);
        return $code;
    }

    private function locked(Appointment $appointment, callable $operation): mixed
    {
        return $this->em->wrapInTransaction(function () use ($appointment, $operation): mixed {
            $this->em->refresh($appointment, LockMode::PESSIMISTIC_WRITE);
            return $operation();
        });
    }
}
