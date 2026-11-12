<?php

namespace App\Tests\Slots;

use App\Controller\Appointment\SlotApiController;
use App\Entity\AppointmentType;
use App\Entity\ScheduleSetting;
use App\Repository\AppointmentRepository;
use App\Repository\AppointmentTypeRepository;
use App\Repository\ScheduleSettingRepository;
use App\Repository\UnavailabilityRepository;
use App\Repository\UnavailableDayRepository;
use App\Service\ScheduleSettingService;
use App\Service\SlotService;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Misd\PhoneNumberBundle\Doctrine\DBAL\Types\PhoneNumberType;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ContainerControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

final class SlotApiRoutesTest extends TestCase
{
    private EntityManager $em;
    private AppointmentType $type;
    private AppointmentTypeRepository $types;
    private SlotService $slots;
    private HttpKernel $kernel;

    protected function setUp(): void
    {
        if (!Type::hasType('phone_number')) Type::addType('phone_number', PhoneNumberType::class);
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        self::assertSame(['driver' => 'pdo_sqlite', 'memory' => true], $connection->getParams());
        self::assertFalse($connection->isConnected());
        $config = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 2).'/src/Entity'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));
        $this->em = new EntityManager($connection, $config);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);
        $settings = new ScheduleSettingService(new ScheduleSettingRepository($registry));
        $this->types = new AppointmentTypeRepository($registry);
        $this->slots = new SlotService(new AppointmentRepository($registry), new UnavailableDayRepository($registry), new UnavailabilityRepository($registry), $settings);
        $this->type = (new AppointmentType())->setName('Synthetic planning')->setSlug('synthetic-planning')->setDuration(60)->setPrice(10000);
        $this->em->persist($this->type);
        foreach (['open_days'=>'1,2,3,4,5', 'opening_delay_hours'=>'0', 'morning_start'=>'09:00', 'morning_end'=>'12:00', 'afternoon_start'=>'14:00', 'afternoon_end'=>'16:00', 'slot_buffer_minutes'=>'0', 'fixed_slots'=>'1'] as $key=>$value) {
            $this->em->persist((new ScheduleSetting())->setSettingKey($key)->setValue($value));
        }
        $this->em->flush();

        // Real route attributes, routing listener, controller and argument resolver.
        // No application kernel, Dotenv, firewall, mailer or external transport.
        $container = new Container();
        $controller = new SlotApiController($settings);
        $controller->setContainer($container);
        $container->set(SlotApiController::class, $controller);
        $routes = (new AttributeRouteControllerLoader())->load(SlotApiController::class);
        $stack = new RequestStack();
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new RouterListener(new UrlMatcher($routes, new RequestContext()), $stack));
        $this->kernel = new HttpKernel($dispatcher, new ContainerControllerResolver($container), $stack, new ArgumentResolver());
    }

    protected function tearDown(): void
    {
        if (isset($this->em)) {
            $this->em->close();
            $this->em->getConnection()->close();
        }
    }

    private function request(string $path, array $query): array
    {
        $request = Request::create($path, 'GET', ['type'=>$this->type->getId(), ...$query]);
        // Trusted test injection, not query parameters; RouterListener resolves the route.
        $request->attributes->add(['slots'=>$this->slots, 'appointmentTypeRepo'=>$this->types, 'types'=>$this->types]);
        $response = $this->kernel->handle($request, catch: false);
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function testDailyRouteAndAliasReturnSameOpenAndClosedDays(): void
    {
        $monday = new \DateTimeImmutable('next monday +14 days', new \DateTimeZone('Europe/Paris'));
        $query = ['date'=>$monday->format('Y-m-d')];
        $events = $this->request('/api/slots', $query);
        self::assertSame($events, $this->request('/api/fixed-slots', $query));
        self::assertSame(['09:00','10:00','11:00','14:00','15:00'], array_map(fn($e)=>(new \DateTimeImmutable($e['start']))->format('H:i'), $events));
        foreach (['/api/slots','/api/fixed-slots'] as $path) {
            self::assertSame([], $this->request($path, ['date'=>$monday->modify('+6 days')->format('Y-m-d')]));
        }
    }

    public function testRangeRouteSkipsClosedDaysAndExcludesEndDate(): void
    {
        $monday = new \DateTimeImmutable('next monday +14 days', new \DateTimeZone('Europe/Paris'));
        $events = $this->request('/api/fixed-slots-range', ['start'=>$monday->modify('-1 day')->format('Y-m-d'), 'end'=>$monday->modify('+1 day')->format('Y-m-d')]);
        self::assertSame($this->request('/api/slots', ['date'=>$monday->format('Y-m-d')]), $events);
        self::assertCount(5, $events);
        foreach ($events as $event) self::assertSame($monday->format('Y-m-d'), (new \DateTimeImmutable($event['start']))->format('Y-m-d'));
    }

    public function testControllerClampAndServiceBarrierKeepAvailabilityAfterOpeningDelay(): void
    {
        // All seven weekdays guarantee termination and an independently calculable +2 day barrier.
        $repo = new \Doctrine\ORM\EntityRepository($this->em, $this->em->getClassMetadata(ScheduleSetting::class));
        $repo->findOneBy(['setting_key'=>'open_days'])->setValue('1,2,3,4,5,6,7');
        $repo->findOneBy(['setting_key'=>'opening_delay_hours'])->setValue('48');
        $this->em->flush();
        $today = new \DateTimeImmutable('today', new \DateTimeZone('Europe/Paris'));
        $barrier = $today->modify('+2 days');
        self::assertSame([], $this->slots->getAvailableSlots($this->type, $barrier->modify('-1 day')));
        $events = $this->request('/api/slots', ['date'=>$today->modify('-1 day')->format('Y-m-d')]);
        self::assertCount(5, $events);
        foreach ($events as $event) self::assertSame($barrier->format('Y-m-d'), (new \DateTimeImmutable($event['start']))->format('Y-m-d'));
        self::assertSame($events, $this->request('/api/fixed-slots', ['date'=>$today->format('Y-m-d')]));
        self::assertSame([], $this->request('/api/fixed-slots-range', ['start'=>$today->format('Y-m-d'), 'end'=>$barrier->format('Y-m-d')]));
        self::assertSame($events, $this->request('/api/fixed-slots-range', ['start'=>$today->format('Y-m-d'), 'end'=>$barrier->modify('+1 day')->format('Y-m-d')]));
    }
}
