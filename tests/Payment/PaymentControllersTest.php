<?php

namespace App\Tests\Payment;

use App\Controller\Appointment\AppointmentCheckoutController;
use App\Controller\Appointment\AppointmentConfirmationController;
use App\Controller\Appointment\AppointmentPaymentController;
use App\Controller\Appointment\AppointmentPaymentSuccessController;
use App\Enum\AppointmentStatus;
use App\Form\AppointmentCheckoutFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Validator\Validation;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class PaymentControllersTest extends PaymentTestCase
{
    private Container $container;
    private CsrfTokenManager $csrf;

    protected function setUp(): void
    {
        parent::setUp();
        $stack = new RequestStack();
        $request = Request::create('/');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $stack->push($request);
        $this->csrf = new CsrfTokenManager(null, new SessionTokenStorage($stack));
        $factory = Forms::createFormFactoryBuilder()->addExtension(new HttpFoundationExtension())
            ->addExtension(new CsrfExtension($this->csrf))
            ->addExtension(new ValidatorExtension(Validation::createValidator()))->getFormFactory();
        $routes = new RouteCollection();
        foreach (['app_home' => '/', 'app_appointment_list' => '/rendez-vous/list',
            'app_appointment_checkout' => '/rendez-vous/checkout/{id}', 'appointment_payment_form' => '/rendez-vous/pay/{id}',
            'app_appointment_payment_success' => '/rendez-vous/terminate/{id}'] as $name => $path) {
            $routes->add($name, new Route($path));
        }
        $this->container = new Container();
        $this->container->set('router', new UrlGenerator($routes, new RequestContext()));
        $this->container->set('request_stack', $stack);
        $this->container->set('form.factory', $factory);
        $this->container->set('twig', new Environment(new ArrayLoader([
            'appointment/checkout.html.twig' => 'checkout', 'appointment/payment.html.twig' => 'payment',
        ])));
        $storage = new TokenStorage();
        $storage->setToken(new UsernamePasswordToken($this->appointment->getUser(), 'main', ['ROLE_USER']));
        $this->container->set('security.token_storage', $storage);
    }

    public function testReturnVisitWithFakeBrowserParametersCannotConfirm(): void
    {
        $controller = $this->wire(new AppointmentPaymentSuccessController($this->payments));
        $request = Request::create('/rendez-vous/terminate/1?payment_intent=pi_fake&redirect_status=succeeded&amount=15000');
        $request->setSession($this->container->get('request_stack')->getSession());
        $this->container->get('request_stack')->push($request);
        self::assertSame(302, $controller->success($this->appointment)->getStatusCode());
        self::assertSame(AppointmentStatus::PENDING, $this->persisted()->getStatus());
        self::assertNull($this->appointment->getNumber());
        self::assertSame(0, $this->mailCount());
    }

    public function testClientRoutesRejectOtherOwnerBeforePaymentSideEffects(): void
    {
        $other = $this->newAppointment()->getUser();
        $this->container->get('security.token_storage')->setToken(new UsernamePasswordToken($other, 'main', ['ROLE_USER']));
        $this->mockStripe();
        $this->stripe->expects(self::never())->method('createAppointmentIntent');
        $this->stripe->expects(self::never())->method('retrievePaymentIntent');
        $controllers = [
            $this->wire(new AppointmentCheckoutController())->index($this->appointment),
            $this->wire(new AppointmentConfirmationController())->confirm($this->appointment, $this->validRequest(), $this->payments),
            $this->wire(new AppointmentPaymentController($this->stripe, $this->payments))->showCardForm($this->appointment),
            $this->wire(new AppointmentPaymentSuccessController($this->payments))->success($this->appointment),
        ];
        foreach ($controllers as $response) {
            self::assertSame(302, $response->getStatusCode());
        }
        self::assertNull($this->persisted()->getPayment()->acceptedAt);
        self::assertNull($this->appointment->getPayment()->intentId);
        self::assertNull($this->appointment->getNumber());
        self::assertSame(0, $this->mailCount());
    }

    public function testMissingCsrfInvalidTokenAndUncheckedTermsDoNotAuthorizePayment(): void
    {
        $controller = $this->wire(new AppointmentConfirmationController());
        $token = $this->csrf->getToken('appointment_payment_confirmation')->getValue();
        foreach ([['agreeTerms' => '1'], ['agreeTerms' => '1', '_token' => 'invalid'], ['_token' => $token]] as $fields) {
            $request = Request::create('/rendez-vous/confirm/1', 'POST', ['appointment_checkout_form' => $fields]);
            self::assertSame(422, $controller->confirm($this->appointment, $request, $this->payments)->getStatusCode());
            self::assertNull($this->persisted()->getPayment()->acceptedAt);
        }
        $pay = $this->wire(new AppointmentPaymentController($this->stripe, $this->payments));
        self::assertSame('/rendez-vous/checkout/'.$this->appointment->getId(), $pay->showCardForm($this->appointment)->headers->get('Location'));
        self::assertNull($this->persisted()->getPayment()->intentId);
        self::assertSame(0, $this->mailCount());
    }

    public function testValidPostAuthorizesPaymentButGetDoesNot(): void
    {
        $controller = $this->wire(new AppointmentConfirmationController());
        self::assertSame(422, $controller->confirm($this->appointment, Request::create('/confirm', 'GET'), $this->payments)->getStatusCode());
        self::assertNull($this->persisted()->getPayment()->acceptedAt);
        $response = $controller->confirm($this->appointment, $this->validRequest(), $this->payments);
        self::assertSame('/rendez-vous/pay/'.$this->appointment->getId(), $response->headers->get('Location'));
        self::assertNotNull($this->persisted()->getPayment()->acceptedAt);
        self::assertSame(15000, $this->appointment->getPayment()->amount);
        self::assertSame(AppointmentStatus::PENDING, $this->appointment->getStatus());
        self::assertNull($this->appointment->getPayment()->intentId);
    }

    private function validRequest(): Request
    {
        return Request::create('/confirm', 'POST', ['appointment_checkout_form' => [
            'agreeTerms' => '1', '_token' => $this->csrf->getToken('appointment_payment_confirmation')->getValue(),
        ]]);
    }

    public function testReturnAfterConfirmedThenCanceledExplainsPreservedCancellation(): void
    {
        $intent = $this->prepare();
        $this->payments->confirmWebhook($intent);
        $this->appointment->setStatus(AppointmentStatus::CANCELED);
        $this->em->flush();
        $this->stripe->method('retrievePaymentIntent')->willReturn($intent);
        $controller = $this->wire(new AppointmentPaymentSuccessController($this->payments));
        self::assertSame(302, $controller->success($this->appointment)->getStatusCode());
        $flashes = $this->container->get('request_stack')->getSession()->getFlashBag()->all();
        self::assertSame(['info' => ['Ce paiement a déjà été pris en compte. Le rendez-vous a ensuite été annulé et reste annulé.']], $flashes);
        self::assertSame(AppointmentStatus::CANCELED, $this->persisted()->getStatus());
        self::assertNull($this->appointment->getPayment()->issue);
        self::assertSame(0, $this->mailCount());
    }

    private function wire(AbstractController $controller): mixed
    {
        $controller->setContainer($this->container);
        return $controller;
    }
}
