<?php

namespace App\Controller;

use App\Service\AppointmentPaymentService;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StripeWebhookController
{
    public function __construct(
        private readonly AppointmentPaymentService $payments,
        #[Autowire(env: 'STRIPE_WEBHOOK_SECRET')]
        private readonly string $webhookSecret,
    ) {}

    #[Route('/stripe/webhook', name: 'app_stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        if ($this->webhookSecret === '') {
            return new Response('Webhook unavailable', Response::HTTP_SERVICE_UNAVAILABLE);
        }
        try {
            $event = Webhook::constructEvent(
                $request->getContent(), $request->headers->get('Stripe-Signature', ''), $this->webhookSecret,
            );
        } catch (\UnexpectedValueException|SignatureVerificationException) {
            return new Response('Invalid signature or payload', Response::HTTP_BAD_REQUEST);
        }
        if ($event->type === 'payment_intent.succeeded' && $event->data->object instanceof PaymentIntent) {
            // Unexpected infrastructure failures intentionally propagate as 5xx for Stripe retries.
            $this->payments->confirmWebhook($event->data->object);
        }
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
