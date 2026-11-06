<?php

namespace App\Tests\Slots;

use App\Controller\Admin\UnavailabilityCrudController;
use App\Doctrine\Listener\NormalizeAppointmentTimezoneListener;
use App\Doctrine\Type\UtcDateTimeImmutableType;
use App\Entity\AppointmentType;
use App\Entity\ScheduleSetting;
use App\Entity\Unavailability;
use App\Repository\AppointmentRepository;
use App\Repository\ScheduleSettingRepository;
use App\Repository\UnavailabilityRepository;
use App\Repository\UnavailableDayRepository;
use App\Service\ScheduleSettingService;
use App\Service\SlotService;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Context\CrudContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Configurator\DateTimeConfigurator;
use EasyCorp\Bundle\EasyAdminBundle\Intl\IntlFormatter;
use Misd\PhoneNumberBundle\Doctrine\DBAL\Types\PhoneNumberType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

final class UnavailabilityTimezoneTest extends TestCase
{
    private EntityManager $em;
    private ManagerRegistry $registry;
    private UnavailabilityRepository $repo;
    private UnavailabilityCrudController $crud;
    private string $previousTimezone;

    protected function setUp(): void
    {
        $this->previousTimezone = date_default_timezone_get();
        // Same standalone ORM setup as PaymentTestCase, without payment fixtures/services.
        $config = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 2).'/src/Entity'], true);
        $config->setNamingStrategy(new UnderscoreNamingStrategy(CASE_LOWER));
        $config->setRepositoryFactory(new class implements RepositoryFactory {
            public function getRepository(EntityManagerInterface $em, string $entityName): EntityRepository
            {
                return new EntityRepository($em, $em->getClassMetadata($entityName));
            }
        });
        foreach (['phone_number' => PhoneNumberType::class, UtcDateTimeImmutableType::NAME => UtcDateTimeImmutableType::class] as $name => $class) {
            if (!Type::hasType($name)) Type::addType($name, $class);
        }
        // No Dotenv, application kernel, network transport or inherited DATABASE_URL.
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        self::assertSame(['driver' => 'pdo_sqlite', 'memory' => true], $connection->getParams());
        $this->em = new EntityManager($connection, $config);
        $this->em->getEventManager()->addEventListener(['prePersist', 'preUpdate'], new NormalizeAppointmentTimezoneListener());
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $this->registry = $this->createStub(ManagerRegistry::class);
        $this->registry->method('getManagerForClass')->willReturn($this->em);
        $this->repo = new UnavailabilityRepository($this->registry);
        $this->crud = new UnavailabilityCrudController();
    }

    protected function tearDown(): void
    {
        if (isset($this->em)) {
            $this->em->close();
            $this->em->getConnection()->close();
        }
        date_default_timezone_set($this->previousTimezone);
    }

    public static function days(): iterable
    {
        foreach (['winter' => '2027-01-12', 'summer' => '2027-07-12', 'spring DST' => '2026-03-29', 'autumn DST' => '2026-10-25'] as $season => $day) {
            foreach (['UTC', 'Europe/Paris'] as $tz) yield $season.' / '.$tz => [$day, $tz];
        }
    }

    public static function periods(): iterable
    {
        foreach (self::days() as $label => [$day, $tz]) {
            $date = new \DateTimeImmutable($day, new \DateTimeZone('Europe/Paris'));
            $prev = $date->modify('-1 day')->format('Y-m-d');
            $next = $date->modify('+1 day')->format('Y-m-d');
            // Independent oracle: Paris civil days occupied, with touching endpoints excluded.
            foreach ([
                'early' => ["$day 00:15", "$day 00:45", [0]],
                'late' => ["$day 23:15", "$day 23:45", [0]],
                'ordinary' => ["$day 09:00", "$day 18:00", [0]],
                'transition' => ["$day 01:30", "$day 03:30", [0]],
                'cross-midnight (direct ORM, rejected by admin)' => ["$day 23:45", "$next 00:15", [0, 1]],
                'ends-at-start (direct ORM, rejected by admin)' => ["$prev 23:30", "$day 00:00", [-1]],
                'starts-at-next-day' => ["$next 00:00", "$next 00:30", [1]],
                'last-second (direct ORM, beyond minute widget)' => ["$day 23:59:59", "$next 00:00:00", [0]],
            ] as $case => $values) yield $label.' / '.$case => [$day, $tz, ...$values];
        }
    }

    #[DataProvider('periods')]
    public function testRepositorySelectsExactlyTheOccupiedParisDays(string $day, string $timezone, string $start, string $end, array $occupied): void
    {
        date_default_timezone_set($timezone);
        // Direct ORM fixtures intentionally include periods the admin's same-day constraint rejects.
        $entity = (new Unavailability())->setStartAt($this->paris($start)->setTimezone(new \DateTimeZone('UTC')))
            ->setEndAt($this->paris($end)->setTimezone(new \DateTimeZone('UTC')))->setAllDay(false);
        $this->em->persist($entity);
        $this->em->flush();
        $id = $entity->getId();
        $this->em->clear();
        foreach ([-1, 0, 1] as $offset) {
            $date = $this->paris($day)->modify(sprintf('%+d day', $offset));
            $ids = array_map(static fn(Unavailability $u) => $u->getId(), $this->repo->findForDate($date));
            self::assertSame(in_array($offset, $occupied, true) ? [$id] : [], $ids, 'Day offset '.$offset);
            self::assertFalse($this->repo->hasAllDay($date));
        }
        $stored = $this->em->find(Unavailability::class, $id);
        $this->assertInstant($this->paris($start), $stored->getStartAt());
        $this->assertInstant($this->paris($end), $stored->getEndAt());
    }

    public static function administrativePeriods(): iterable
    {
        foreach (self::days() as $label => $args) {
            yield $label.' / hourly' => [...$args, false];
            yield $label.' / allDay' => [...$args, true];
        }
    }

    #[DataProvider('administrativePeriods')]
    public function testAdministrativeRoundTripPreservesDatesAndClosure(string $day, string $timezone, bool $allDay): void
    {
        date_default_timezone_set($timezone);
        $entity = (new Unavailability())->setAllDay($allDay);
        $form = $this->form($entity);
        $form->submit(['startAt' => $day.'T09:00', 'endAt' => $day.'T18:00']);
        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        self::assertInstanceOf(\DateTimeImmutable::class, $form->get('startAt')->getData());
        self::assertInstanceOf(\DateTimeImmutable::class, $form->get('endAt')->getData());
        self::assertSame('UTC', $entity->getStartAt()->getTimezone()->getName());
        $start = $this->paris($day.($allDay ? ' 00:00:00' : ' 09:00:00'));
        $end = $this->paris($day.($allDay ? ' 23:59:59' : ' 18:00:00'));
        $this->crud->persistEntity($this->em, $entity);
        $id = $entity->getId();
        $expected = [$start->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'), $end->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s')];
        self::assertSame($expected, $this->raw($id));
        $this->em->clear();
        $loaded = $this->em->find(Unavailability::class, $id);
        $this->assertInstant($start, $loaded->getStartAt());
        $this->assertInstant($end, $loaded->getEndAt());
        foreach ([-1, 0, 1] as $offset) {
            $date = $this->paris($day)->modify(sprintf('%+d day', $offset));
            self::assertCount($offset === 0 ? 1 : 0, $this->repo->findForDate($date));
            self::assertSame($allDay && $offset === 0, $this->repo->hasAllDay($date));
        }
        // Editing the unchanged visible values (minute precision) must remain valid and stable.
        $edit = $this->form($loaded);
        $edit->submit(['startAt' => $edit->get('startAt')->getViewData(), 'endAt' => $edit->get('endAt')->getViewData()]);
        self::assertTrue($edit->isValid(), (string) $edit->getErrors(true));
        $this->crud->updateEntity($this->em, $loaded);
        self::assertSame($expected, $this->raw($id));
        $this->em->clear();
        $updated = $this->em->find(Unavailability::class, $id);
        $this->assertInstant($start, $updated->getStartAt());
        $this->assertInstant($end, $updated->getEndAt());
    }

    #[DataProvider('days')]
    public function testAllDayCanBeReboundWithoutClearing(string $day, string $timezone): void
    {
        date_default_timezone_set($timezone);
        $entity = (new Unavailability())->setAllDay(true);
        $form = $this->form($entity);
        $form->submit(['startAt' => $day.'T09:00', 'endAt' => $day.'T18:00']);
        self::assertTrue($form->isValid());
        $this->crud->persistEntity($this->em, $entity);
        foreach (['persist', 'update'] as $stage) {
            // Real DateTimeType rejects a Paris object with model_timezone=UTC here.
            $rebound = $this->form($entity);
            self::assertSame($day.'T00:00', $rebound->get('startAt')->getViewData(), $stage);
            self::assertSame($day.'T23:59', $rebound->get('endAt')->getViewData(), $stage);
            $this->assertInstant($this->paris($day.' 00:00:00'), $entity->getStartAt());
            $this->assertInstant($this->paris($day.' 23:59:59'), $entity->getEndAt());
            if ($stage === 'persist') {
                $rebound->submit(['startAt' => $day.'T00:00', 'endAt' => $day.'T23:59']);
                self::assertTrue($rebound->isValid());
                $this->crud->updateEntity($this->em, $entity);
            }
        }
    }

    #[DataProvider('days')]
    public function testAdminStillRejectsMultipleDays(string $day, string $timezone): void
    {
        date_default_timezone_set($timezone);
        $entity = (new Unavailability())->setAllDay(true);
        $form = $this->form($entity);
        $next = $this->paris($day)->modify('+1 day')->format('Y-m-d');
        $form->submit(['startAt' => $day.'T09:00', 'endAt' => $next.'T18:00']);
        self::assertFalse($form->isValid());
        self::assertStringContainsString("L'indisponibilité doit se situer sur une seule journée", (string) $form->getErrors(true));
        self::assertSame(0, (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM unavailability'));
    }

    public static function slotPeriods(): iterable
    {
        // Future civil dates without modifying SlotService's clock or booking barrier.
        $year = (int) (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y') + 1;
        foreach (['winter' => "$year-01-12", 'summer' => "$year-07-12"] as $season => $day) {
            foreach (['UTC', 'Europe/Paris'] as $timezone) {
                foreach (['early', 'ordinary', 'allDay'] as $case) yield "$season / $timezone / $case" => [$day, $timezone, $case];
            }
        }
    }

    #[DataProvider('slotPeriods')]
    public function testExactAvailableSlots(string $day, string $timezone, string $case): void
    {
        date_default_timezone_set($timezone);
        foreach (['open_days' => '1,2,3,4,5,6,7', 'opening_delay_hours' => '0', 'fixed_slots' => '0',
            'slot_step_minutes' => '15', 'slot_buffer_minutes' => '0', 'morning_start' => '00:00',
            'morning_end' => '02:00', 'afternoon_start' => '09:00', 'afternoon_end' => '12:00'] as $key => $value) {
            $this->em->persist((new ScheduleSetting())->setSettingKey($key)->setValue($value));
        }
        [$start, $end, $blocked] = $case === 'early' ? ['00:15', '00:45', ['00:15', '00:30']]
            : ['10:00', '11:00', ['10:00', '10:15', '10:30', '10:45']];
        $entity = (new Unavailability())->setAllDay($case === 'allDay');
        $form = $this->form($entity);
        $form->submit(['startAt' => $day.'T'.$start, 'endAt' => $day.'T'.$end]);
        self::assertTrue($form->isValid());
        $this->crud->persistEntity($this->em, $entity);
        $this->em->clear();
        $service = new SlotService(new AppointmentRepository($this->registry), new UnavailableDayRepository($this->registry),
            $this->repo, new ScheduleSettingService(new ScheduleSettingRepository($this->registry)));
        $slots = $service->getAvailableSlots((new AppointmentType())->setDuration(15), $this->paris($day));
        $grid = ['00:00','00:15','00:30','00:45','01:00','01:15','01:30','01:45',
            '09:00','09:15','09:30','09:45','10:00','10:15','10:30','10:45','11:00','11:15','11:30','11:45'];
        $expected = $case === 'allDay' ? [] : array_values(array_diff($grid, $blocked));
        self::assertSame($expected, array_map(static fn(array $s) => $s['start']->format('H:i'), $slots));
        foreach ($slots as $slot) {
            self::assertSame(900, $slot['end']->getTimestamp() - $slot['start']->getTimestamp());
            self::assertSame('Europe/Paris', $slot['start']->getTimezone()->getName());
        }
    }

    #[DataProvider('days')]
    public function testLegacyParisWallClockRowStillRequiresADataDecision(string $day, string $timezone): void
    {
        date_default_timezone_set($timezone);
        // Deliberately bypass the new writer: simulate the OLD allDay storage convention.
        $this->em->getConnection()->insert('unavailability', ['start_at' => $day.' 00:00:00', 'end_at' => $day.' 23:59:59', 'all_day' => 1]);
        $this->em->clear();
        $loaded = $this->repo->findForDate($this->paris($day))[0];
        self::assertNotSame($this->paris($day)->getTimestamp(), $loaded->getStartAt()->getTimestamp());
        // Characterization of a known unresolved legacy risk, NOT desired business behavior.
        self::assertTrue($this->repo->hasAllDay($this->paris($day)->modify('+1 day')));
    }

    private function form(Unavailability $entity): FormInterface
    {
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $factory = Forms::createFormFactoryBuilder()->addExtension(new ValidatorExtension($validator))->getFormFactory();
        $dto = new EntityDto(Unavailability::class, $this->em->getClassMetadata(Unavailability::class));
        $context = AdminContext::forTesting(crudContext: CrudContext::forTesting(crudDto: $this->crud->configureCrud(Crud::new())->getAsDto(), entityDto: $dto));
        $configurator = new DateTimeConfigurator(new IntlFormatter());
        $builder = $factory->createBuilder(FormType::class, $entity, ['data_class' => Unavailability::class]);
        foreach ($this->crud->configureFields(Crud::PAGE_NEW) as $field) {
            $fieldDto = $field->getAsDto();
            if (!in_array($fieldDto->getProperty(), ['startAt', 'endAt'], true)) continue;
            $configurator->configure($fieldDto, $dto, $context);
            self::assertSame('datetime_immutable', $fieldDto->getFormTypeOption('input'));
            self::assertSame('UTC', $fieldDto->getFormTypeOption('model_timezone'));
            self::assertSame('Europe/Paris', $fieldDto->getFormTypeOption('view_timezone'));
            $builder->add($fieldDto->getProperty(), $fieldDto->getFormType(), $fieldDto->getFormTypeOptions());
        }
        return $builder->getForm();
    }

    private function paris(string $value): \DateTimeImmutable
    {
        return new \DateTimeImmutable($value, new \DateTimeZone('Europe/Paris'));
    }

    private function assertInstant(\DateTimeImmutable $expected, \DateTimeImmutable $actual): void
    {
        self::assertSame($expected->getTimestamp(), $actual->getTimestamp());
        self::assertSame('UTC', $actual->getTimezone()->getName());
    }

    private function raw(int $id): array
    {
        return $this->em->getConnection()->fetchNumeric('SELECT start_at, end_at FROM unavailability WHERE id = ?', [$id]);
    }
}
