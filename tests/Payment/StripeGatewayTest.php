<?php

namespace App\Tests\Payment;

use App\Stripe\StripeService;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

final class StripeGatewayTest extends PaymentTestCase
{
    public function testActualStripeSdkReceivesStableServerParametersAndIdempotencyKey(): void
    {
        $this->payments->acceptTerms($this->appointment);
        $response = $this->intent()->toArray();
        $http = new class($response) implements ClientInterface {
            public array $calls = [];
            public function __construct(private array $response) {}
            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                $this->calls[] = [$method, $absUrl, $headers, $params];
                return [json_encode($this->response, JSON_THROW_ON_ERROR), 200, []];
            }
        };
        $previous = ApiRequestor::httpClient();
        ApiRequestor::setHttpClient($http);
        try {
            $gateway = new StripeService('sk_test_local_fake', 'pk_test_local_fake');
            $first = $gateway->createAppointmentIntent($this->appointment);
            $this->appointment->getType()->setPrice(99900)->setName('Changed later');
            $second = $gateway->createAppointmentIntent($this->appointment);
            $gateway->retrievePaymentIntent($first->id);
            self::assertSame($first->id, $second->id);
            self::assertSame($http->calls[0][3], $http->calls[1][3]);
            self::assertSame(15000, $http->calls[0][3]['amount']);
            self::assertSame('eur', $http->calls[0][3]['currency']);
            self::assertSame((string) $this->appointment->getId(), $http->calls[0][3]['metadata']['appointment_id']);
            self::assertContains('Idempotency-Key: '.$this->appointment->getPayment()->attemptKey, $http->calls[0][2]);
            self::assertContains('Idempotency-Key: '.$this->appointment->getPayment()->attemptKey, $http->calls[1][2]);
            self::assertSame('get', $http->calls[2][0]);
            self::assertStringEndsWith('/payment_intents/'.$first->id, $http->calls[2][1]);
        } finally {
            ApiRequestor::setHttpClient($previous);
        }
    }
}
