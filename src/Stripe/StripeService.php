<?php

namespace App\Stripe;

use App\Entity\Appointment;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripeService
{
    private StripeClient $client;

    public function __construct(
        private readonly string $secretKey,
        private readonly string $publicKey
    ) {
        $this->client = new StripeClient($this->secretKey);
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /** All request parameters are stable across retries with the same persisted key. */
    private function createPaymentIntent(int $amount, array $metadata, string $idempotencyKey): PaymentIntent
    {
        return $this->client->paymentIntents->create([
            'amount'               => $amount,
            'currency'             => 'eur',
            'payment_method_types' => ['card'], // 'paypal' à ajouter dans le tableau ['card', 'paypal'] si activé dans Stripe
            'metadata'             => array_filter($metadata, fn($v) => $v !== null && $v !== ''),
            'description'          => 'Rendez-vous #'.$metadata['appointment_id'],
        ], ['idempotency_key' => $idempotencyKey]);
    }

    /**
     * RDV (Appointment) — PaymentIntent avec metadata et description dédiée
     */
    public function createAppointmentIntent(Appointment $appointment): PaymentIntent
    {
        $payment = $appointment->getPayment();
        if (!$payment->acceptedAt || !$payment->attemptKey || !$payment->amount || $payment->currency !== 'eur') {
            throw new \LogicException('Payment has not been prepared.');
        }

        return $this->createPaymentIntent(
            $payment->amount,
            [
                'kind' => 'appointment',
                'appointment_id'    => (string) $appointment->getId(),
                'attempt_key' => $payment->attemptKey,
            ],
            $payment->attemptKey
        );
    }

    public function retrievePaymentIntent(string $id): PaymentIntent
    {
        return $this->client->paymentIntents->retrieve($id, []);
    }
}
