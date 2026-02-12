<?php

namespace App\Stripe;

use App\Entity\Appointment;
use App\Entity\Purchase;
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

    /**
     * Méthode générique : on créé un PaymentIntent avec metedata et description
     *
     * @param integer $amount
     * @param array $metadata
     * @param string|null $description
     */
    public function createPaymentIntent(int $amount, array $metadata = [], ?string $description = null)
    {
        return $this->client->paymentIntents->create([
            'amount'               => $amount,
            'currency'             => 'eur',
            'payment_method_types' => ['card'], // 'paypal' à ajouter dans le tableau ['card', 'paypal'] si activé dans Stripe
            'metadata'             => array_filter($metadata, fn($v) => $v !== null && $v !== ''),
            'description'          => $description,
        ]);
    }

    /**
     * RDV (Appointment) — PaymentIntent avec metadata et description dédiée
     */
    public function getPaymentIntentForAppointment(Appointment $appointment)
    {
        $type = $appointment->getType();
        $user = $appointment->getUser();
        $amount = (int) $type->getPrice();

        return $this->createPaymentIntent(
            $amount,
            [
                'kind' => 'appointment',
                'appointment_id'    => (string) $appointment->getId(),
                'appointment_type'  => $type ? (string) $type->getName() : null,
                'start_at'          => $appointment->getStartAt()?->format('c'), // ISO 8601 (UTC)
                'end_at'            => $appointment->getEndAt()?->format('c'),
                'user_id'           => $user ? (string) $user->getId() : null,
                'user_email'        => $user ? (string) $user->getEmail() : null,
                'appointment_num'   => method_exists($appointment, 'getNumber') ? (string) $appointment->getNumber() : null,
            ],
            $type
                ? sprintf('RDV • %s • %s', $type->getName(), $appointment->getStartAt()?->format('Y-m-d H:i'))
                : 'RDV'
        );
    }
}
