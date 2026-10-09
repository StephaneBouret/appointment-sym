<?php

namespace App\Tests\Payment;

use App\Entity\Appointment;
use App\Entity\AppointmentType;
use App\Entity\User;
use App\Event\AppointmentSuccessEvent;
use App\Service\AppointmentPaymentService;
use App\Service\PaymentNotificationService;
use App\Service\PurchaseNumberGenerator;
use App\Stripe\StripeService;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\ORM\Tools\SchemaTool;
use Misd\PhoneNumberBundle\Doctrine\DBAL\Types\PhoneNumberType;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Stripe\PaymentIntent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mime\Email;

abstract class PaymentTestCase extends TestCase
{
    protected EntityManager $em;
    protected StripeService $stripe;
    protected AppointmentPaymentService $payments;
    protected PaymentNotificationService $notifications;
    protected EventDispatcher $events;
    protected MessageLoggerListener $mailLog;
    protected Appointment $appointment;
    protected array $deliveredNumbers = [];

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 2).'/src/Entity'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));
        // Application repositories require Symfony's registry. Basic ORM repositories suffice here.
        $config->setRepositoryFactory(new class implements RepositoryFactory {
            public function getRepository(EntityManagerInterface $em, string $entityName): EntityRepository
            {
                return new EntityRepository($em, $em->getClassMetadata($entityName));
            }
        });
        if (!Type::hasType('phone_number')) {
            Type::addType('phone_number', PhoneNumberType::class);
        }
        // Hard-coded in-memory target; DATABASE_URL and local credentials are never consulted.
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        self::assertSame(['driver' => 'pdo_sqlite', 'memory' => true], $connection->getParams());
        $this->em = new EntityManager($connection, $config);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->stripe = $this->createStub(StripeService::class);
        $this->events = new EventDispatcher();
        $mailEvents = new EventDispatcher();
        $this->mailLog = new MessageLoggerListener();
        $mailEvents->addSubscriber($this->mailLog);
        $mailer = new Mailer(new NullTransport($mailEvents));
        $this->events->addListener(AppointmentSuccessEvent::NAME, function (AppointmentSuccessEvent $event) use ($mailer): void {
            $appointment = $event->getAppointment();
            // Real Symfony mailer, no external transport. Inspect the durable state at dispatch time.
            self::assertFalse($this->em->getConnection()->isTransactionActive());
            self::assertSame('sending', $appointment->getPayment()->notificationState);
            $mailer->send((new Email())->from('sender@example.test')->to('client@example.test')
                ->subject('Confirmation '.$appointment->getNumber())->text((string) $appointment->getPayableAmount()));
            $this->deliveredNumbers[] = $appointment->getNumber();
        });
        $this->notifications = new PaymentNotificationService($this->em, $this->events, new NullLogger());
        $this->payments = new AppointmentPaymentService($this->em, $this->stripe, new PurchaseNumberGenerator(), $this->notifications, new NullLogger());
        $this->appointment = $this->newAppointment();
    }

    protected function tearDown(): void
    {
        $this->em->close();
        $this->em->getConnection()->close();
    }

    protected function newAppointment(): Appointment
    {
        $user = (new User())->setEmail(bin2hex(random_bytes(6)).'@example.test')->setPassword('unused-test-hash')
            ->setFirstname('Client')->setLastname('Fictif')->setAdress('1 rue de test')->setPostalCode('75001')->setCity('Paris')
            ->setPhone(\libphonenumber\PhoneNumberUtil::getInstance()->parse('0600000000', 'FR'));
        $type = (new AppointmentType())->setName('Prestation fictive')->setSlug('test-'.bin2hex(random_bytes(6)))
            ->setPrice(15000)->setDuration(90);
        $appointment = (new Appointment())->setUser($user)->setType($type)
            ->setStartAt(new \DateTimeImmutable('+3 days'))->setEndAt(new \DateTimeImmutable('+3 days +90 minutes'));
        $this->em->persist($user);
        $this->em->persist($type);
        $this->em->persist($appointment);
        $this->em->flush();
        return $appointment;
    }

    protected function mockStripe(): void
    {
        $this->stripe = $this->createMock(StripeService::class);
        $this->payments = new AppointmentPaymentService($this->em, $this->stripe, new PurchaseNumberGenerator(), $this->notifications, new NullLogger());
    }

    protected function prepare(?Appointment $appointment = null): PaymentIntent
    {
        $appointment ??= $this->appointment;
        self::assertTrue($this->payments->acceptTerms($appointment));
        $appointment->getPayment()->intentId = 'pi_test_'.$appointment->getId();
        $this->em->flush();
        return $this->intent($appointment);
    }

    protected function intent(?Appointment $appointment = null, array $overrides = []): PaymentIntent
    {
        $appointment ??= $this->appointment;
        return PaymentIntent::constructFrom(array_replace([
            'id' => $appointment->getPayment()->intentId ?? 'pi_test_'.$appointment->getId(),
            'object' => 'payment_intent', 'status' => 'succeeded',
            'amount' => 15000, 'amount_received' => 15000, 'currency' => 'eur', 'client_secret' => 'test-only',
            'metadata' => ['kind' => 'appointment', 'appointment_id' => (string) $appointment->getId(),
                'attempt_key' => $appointment->getPayment()->attemptKey],
        ], $overrides));
    }

    protected function persisted(): Appointment
    {
        $this->em->refresh($this->appointment);
        return $this->appointment;
    }

    protected function mailCount(): int
    {
        return count($this->mailLog->getEvents()->getMessages());
    }
}
