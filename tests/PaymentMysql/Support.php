<?php

declare(strict_types=1);

namespace App\Tests\PaymentMysql;

use App\Entity\Appointment;
use App\Entity\AppointmentType;
use App\Entity\User;
use App\Event\AppointmentSuccessEvent;
use App\Service\AppointmentPaymentService;
use App\Service\PaymentNotificationService;
use App\Service\PurchaseNumberGenerator;
use App\Stripe\StripeService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Repository\RepositoryFactory;
use Misd\PhoneNumberBundle\Doctrine\DBAL\Types\PhoneNumberType;
use Psr\Log\NullLogger;
use Stripe\PaymentIntent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mime\Email;

// Reuse the fail-closed Stripe HTTP blocker; never load Dotenv or the app kernel.
require_once dirname(__DIR__).'/Payment/bootstrap.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new \RuntimeException($message);
    }
}

function writeJson(string $path, array $value): void
{
    // Readers only see complete barrier messages (atomic rename, same directory).
    $temporary = $path.'.'.getmypid().'.tmp';
    file_put_contents($temporary, json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    rename($temporary, $path);
}

function readJson(string $path): array
{
    return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
}

function until(callable $condition, string $failure, float $seconds = 20): void
{
    $deadline = microtime(true) + $seconds;
    do {
        if ($condition()) {
            return;
        }
        usleep(20000); // Poll a condition; elapsed time never releases a barrier.
    } while (microtime(true) < $deadline);
    throw new \RuntimeException($failure);
}

function signal(string $directory, string $worker, string $event, array $data = []): void
{
    writeJson($directory.'/'.$worker.'.'.$event.'.json', $data + ['pid' => getmypid()]);
}

function waitRelease(string $directory, string $worker, string $event): void
{
    until(fn () => is_file($directory.'/'.$worker.'.release-'.$event), 'Barrier timeout: '.$worker.'/'.$event);
}

function connectionParameters(?string $database = null): array
{
    // Only this dedicated credential namespace is read. No DATABASE_URL or .env.
    $params = ['driver' => 'pdo_mysql', 'host' => '127.0.0.1', 'port' => 3306,
        'user' => getenv('PAYMENT_MYSQL_USER') ?: 'root',
        'password' => getenv('PAYMENT_MYSQL_PASSWORD') ?: '', 'charset' => 'utf8mb4'];
    if ($database !== null) {
        check((bool) preg_match('/^appointment_payment_recette_[0-9]{8}_[a-f0-9]{12}$/D', $database), 'Unsafe database name');
        $params['dbname'] = $database;
    }
    return $params;
}

function identity(Connection $connection): array
{
    return $connection->fetchAssociative('SELECT @@hostname AS server, @@port AS port, DATABASE() AS db, VERSION() AS version, @@server_uuid AS uuid, @@default_storage_engine AS engine, @@transaction_isolation AS isolation_level, CONNECTION_ID() AS connection_id');
}

function guardedConnection(array $target, ?Middleware $middleware = null): Connection
{
    $config = new \Doctrine\DBAL\Configuration();
    if ($middleware) {
        $config->setMiddlewares([$middleware]);
    }
    $connection = DriverManager::getConnection(connectionParameters($target['database']), $config);
    $actual = identity($connection);
    check($actual['db'] === $target['database'], 'Effective database mismatch');
    check($actual['uuid'] === $target['server']['uuid'] && $actual['server'] === $target['server']['server'], 'Effective server mismatch');
    check((int) $actual['port'] === 3306 && $actual['version'] === $target['server']['version'], 'Server port/version mismatch');
    check($actual['engine'] === 'InnoDB', 'InnoDB must be the default engine');
    check((int) $connection->fetchOne('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND ENGINE <> ?', [$target['database'], 'InnoDB']) === 0, 'Non-InnoDB table in dedicated schema');
    // Session settings are only changed AFTER the effective target checks.
    $connection->executeStatement("SET SESSION time_zone = '+00:00'");
    $connection->executeStatement('SET SESSION innodb_lock_wait_timeout = 20');
    return $connection;
}

function entityManager(Connection $connection): EntityManager
{
    $config = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 2).'/src/Entity'], true);
    $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));
    $config->setRepositoryFactory(new class implements RepositoryFactory {
        public function getRepository(EntityManagerInterface $em, string $entityName): EntityRepository
        {
            return new EntityRepository($em, $em->getClassMetadata($entityName));
        }
    });
    if (!Type::hasType('phone_number')) {
        Type::addType('phone_number', PhoneNumberType::class);
    }
    return new EntityManager($connection, $config);
}

function fakeIntent(Appointment $appointment): PaymentIntent
{
    $payment = $appointment->getPayment();
    return PaymentIntent::constructFrom([
        'id' => $payment->intentId, 'object' => 'payment_intent', 'status' => 'succeeded',
        'amount' => $payment->amount, 'amount_received' => $payment->amount, 'currency' => $payment->currency,
        'metadata' => ['kind' => 'appointment', 'appointment_id' => (string) $appointment->getId(), 'attempt_key' => $payment->attemptKey],
    ]);
}

function services(EntityManager $em, string $directory, string $worker, bool $holdMail = false): array
{
    $stripe = new class($em) extends StripeService {
        public function __construct(private EntityManager $em) {} // Never construct a Stripe HTTP client.
        public function retrievePaymentIntent(string $id): PaymentIntent
        {
            $appointment = $this->em->getRepository(Appointment::class)->findOneBy(['payment.intentId' => $id]);
            check($appointment !== null, 'Unknown fake PaymentIntent');
            return fakeIntent($appointment);
        }
        public function createAppointmentIntent(Appointment $appointment): PaymentIntent
        {
            throw new \LogicException('Intent creation forbidden in MySQL acceptance tests');
        }
    };
    $mailEvents = new EventDispatcher();
    $mailEvents->addListener(SentMessageEvent::class, static function () use ($directory, $worker): void {
        file_put_contents($directory.'/mail.ndjson', json_encode(['worker' => $worker, 'pid' => getmypid()], JSON_THROW_ON_ERROR)."\n", FILE_APPEND | LOCK_EX);
    });
    $mailer = new Mailer(new NullTransport($mailEvents));
    $events = new EventDispatcher();
    $events->addListener(AppointmentSuccessEvent::NAME, static function (AppointmentSuccessEvent $event) use ($em, $mailer, $directory, $worker, $holdMail): void {
        check(!$em->getConnection()->isTransactionActive(), 'Transport called within a transaction');
        check($event->getAppointment()->getPayment()->notificationState === 'sending', 'Notification was not claimed');
        if ($holdMail) {
            signal($directory, $worker, 'transport-ready');
            waitRelease($directory, $worker, 'transport');
        }
        $mailer->send((new Email())->from('sender@example.test')->to('client@example.test')
            ->subject('Fictitious confirmation '.$event->getAppointment()->getNumber())->text('Captured locally only'));
    });
    $notifications = new PaymentNotificationService($em, $events, new NullLogger());
    $numbers = new class($directory, $worker) extends PurchaseNumberGenerator {
        public function __construct(private string $directory, private string $worker) {}
        public function generate(): string
        {
            $number = parent::generate();
            file_put_contents($this->directory.'/numbers.ndjson', json_encode(['worker' => $this->worker, 'number' => $number], JSON_THROW_ON_ERROR)."\n", FILE_APPEND | LOCK_EX);
            return $number;
        }
    };
    return [new AppointmentPaymentService($em, $stripe, $numbers, $notifications, new NullLogger()), $notifications];
}

function fixtureOwners(EntityManager $em): array
{
    $user = (new User())->setEmail(bin2hex(random_bytes(6)).'@example.test')->setPassword('unused-fictitious-hash')
        ->setFirstname('Client')->setLastname('Fictif')->setAdress('1 rue fictive')->setPostalCode('75001')->setCity('Paris')
        ->setPhone(\libphonenumber\PhoneNumberUtil::getInstance()->parse('0600000000', 'FR'));
    $type = (new AppointmentType())->setName('Recette fictive')->setSlug('recette-'.bin2hex(random_bytes(6)))->setPrice(15000)->setDuration(90);
    $em->persist($user);
    $em->persist($type);
    $em->flush();
    return [$user, $type];
}

/** Pause after the real SELECT FOR UPDATE or immediately before its transaction commits. */
final class BarrierMiddleware implements Middleware
{
    private bool $locked = false;
    private bool $committed = false;

    public function __construct(private string $directory, private string $worker, private string $hold) {}

    public function afterQuery(string $sql): void
    {
        if (!$this->locked && str_contains(strtoupper($sql), 'FOR UPDATE') && stripos($sql, 'appointment') !== false) {
            $this->locked = true;
            signal($this->directory, $this->worker, 'locked');
            if (in_array($this->hold, ['lock', 'lock-mail'], true)) {
                waitRelease($this->directory, $this->worker, 'lock');
            }
        }
    }

    public function beforeCommit(): void
    {
        if ($this->locked && !$this->committed) {
            $this->committed = true;
            signal($this->directory, $this->worker, 'commit-ready');
            if ($this->hold === 'commit') {
                waitRelease($this->directory, $this->worker, 'commit');
            }
        }
    }

    public function wrap(Driver $driver): Driver
    {
        return new class($driver, $this) extends AbstractDriverMiddleware {
            public function __construct(Driver $driver, private BarrierMiddleware $barrier) { parent::__construct($driver); }
            public function connect(#[\SensitiveParameter] array $params): Driver\Connection
            {
                return new class(parent::connect($params), $this->barrier) extends AbstractConnectionMiddleware {
                    public function __construct(Driver\Connection $connection, private BarrierMiddleware $barrier) { parent::__construct($connection); }
                    public function prepare(string $sql): Driver\Statement
                    {
                        return new class(parent::prepare($sql), $this->barrier, $sql) extends AbstractStatementMiddleware {
                            public function __construct(Driver\Statement $statement, private BarrierMiddleware $barrier, private string $sql) { parent::__construct($statement); }
                            public function execute(): Driver\Result
                            {
                                $result = parent::execute();
                                $this->barrier->afterQuery($this->sql);
                                return $result;
                            }
                        };
                    }
                    public function commit(): void
                    {
                        $this->barrier->beforeCommit();
                        parent::commit();
                    }
                };
            }
        };
    }
}
