<?php

namespace App\Tests\Slots;

use App\Controller\Appointment\SlotApiController;
use App\Entity\ScheduleSetting;
use App\Repository\ScheduleSettingRepository;
use App\Service\ScheduleSettingService;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PlanningContractsTest extends TestCase
{
    private EntityManager $em;
    private ScheduleSettingRepository $repo;
    private ScheduleSettingService $settings;

    protected function setUp(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        self::assertSame(['driver' => 'pdo_sqlite', 'memory' => true], $connection->getParams());
        self::assertFalse($connection->isConnected());
        $config = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 2).'/src/Entity'], true);
        $this->em = new EntityManager($connection, $config);
        (new SchemaTool($this->em))->createSchema([$this->em->getClassMetadata(ScheduleSetting::class)]);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);
        $this->repo = new ScheduleSettingRepository($registry);
        $this->settings = new ScheduleSettingService($this->repo);
    }

    protected function tearDown(): void
    {
        if (isset($this->em)) {
            $this->em->close();
            $this->em->getConnection()->close();
        }
    }

    private function store(string $key, string $value): ScheduleSetting
    {
        $setting = (new ScheduleSetting())->setSettingKey($key)->setValue($value);
        $this->em->persist($setting);
        $this->em->flush();
        return $setting;
    }

    private function sqlValue(string $key): string
    {
        $table = $this->em->getClassMetadata(ScheduleSetting::class)->getTableName();
        return $this->em->getConnection()->fetchOne('SELECT value FROM '.$table.' WHERE setting_key = ?', [$key]);
    }

    public function testEmptyRepositoryDoesNotIncludePendingInsertions(): void
    {
        self::assertSame([], $this->repo->findAllKeyValue());
        $setting = (new ScheduleSetting())->setSettingKey('open_days')->setValue('1,2');
        $this->em->persist($setting);
        self::assertSame([], $this->repo->findAllKeyValue());
        $this->em->flush();
        $this->em->clear();
        self::assertSame(['open_days' => '1,2'], $this->repo->findAllKeyValue());
    }

    public function testHydratedMapPreservesKeyTypesAndUnflushedManagedValues(): void
    {
        $this->store('123', 'numeric');
        $this->store('01', 'leading-zero');
        $this->store('open_days', '1,2,3');
        $this->em->clear();
        $map = $this->repo->findAllKeyValue();
        $expected = [123 => 'numeric', '01' => 'leading-zero', 'open_days' => '1,2,3'];
        // The query has no ORDER BY; key and value types matter, row order does not.
        ksort($map);
        ksort($expected);
        self::assertSame($expected, $map);
        self::assertContains(123, array_keys($map));
        self::assertContains('01', array_keys($map));
        $managed = $this->repo->findOneBy(['setting_key' => 'open_days']);
        $managed->setValue('2,2,1');
        self::assertSame('2,2,1', $this->repo->findAllKeyValue()['open_days']);
        self::assertSame([2,2,1], $this->settings->getCsvIntList('open_days'));
        self::assertSame('1,2,3', $this->sqlValue('open_days'));
        $this->em->clear();
        self::assertSame('1,2,3', $this->repo->findAllKeyValue()['open_days']);
        self::assertSame([1,2,3], $this->settings->getCsvIntList('open_days'));
    }

    public function testPartialManagedValueCanBeNullDespiteNonNullableSqlColumn(): void
    {
        $this->store('open_days', '1,2,3');
        $this->em->clear();
        // This is a Doctrine contract boundary, not an application PARTIAL query.
        $partial = $this->em->createQuery('SELECT PARTIAL s.{id,setting_key} FROM App\\Entity\\ScheduleSetting s WHERE s.setting_key = :key')
            ->setParameter('key', 'open_days')->getSingleResult();
        self::assertNull($partial->getValue());
        self::assertSame(['open_days' => null], $this->repo->findAllKeyValue());
        self::assertSame([4,5], $this->settings->getCsvIntList('open_days', '4,5'));
        self::assertSame('1,2,3', $this->sqlValue('open_days'));
        $this->em->clear();
        self::assertSame(['open_days' => '1,2,3'], $this->repo->findAllKeyValue());
    }

    public function testCsvDefaultAppliesToMissingRowsButNotExistingEmptyStrings(): void
    {
        self::assertSame([], $this->settings->getCsvIntList('open_days'));
        self::assertSame([1,3], $this->settings->getCsvIntList('open_days', '1,, 3, '));
        $this->store('open_days', '');
        self::assertSame([], $this->settings->getCsvIntList('open_days', '1,3'));
    }

    public static function csvCases(): iterable
    {
        yield 'empty' => ['', []];
        yield 'spaces' => ['   ', []];
        yield 'canonical' => ['1,2,3,4,5', [1,2,3,4,5]];
        yield 'gaps and trim' => ['1,, 3, ', [1,3]];
        yield 'order and duplicates' => ['2,2,1', [2,2,1]];
        yield 'existing conversions and negative filtering' => ['-1,0,8,abc,2.9', [0,8,0,2]];
    }

    #[DataProvider('csvCases')]
    public function testCsvReturnsReindexedIntegersWithoutNewNormalization(string $raw, array $expected): void
    {
        $this->store('open_days', $raw);
        $this->em->clear();
        // These adverse inputs never reach availability search loops.
        self::assertSame($expected, $this->settings->getCsvIntList('open_days'));
    }

    public static function openDayCases(): iterable
    {
        yield 'open Monday' => ['2026-11-16', [1,2,3,4,5], true];
        yield 'closed Sunday' => ['2026-11-22', [1,2,3,4,5], false];
        yield 'empty list' => ['2026-11-16', [], false];
        yield 'strict comparison outside the caller contract' => ['2026-11-16', ['1'], false];
        yield 'duplicates' => ['2026-11-16', [1,1], true];
        yield 'integers are not necessarily weekdays' => ['2026-11-16', [0,8], false];
    }

    #[DataProvider('openDayCases')]
    public function testOpenDayConsumerKeepsStrictMembership(string $date, array $days, bool $expected): void
    {
        $controller = new SlotApiController($this->settings);
        $method = new \ReflectionMethod($controller, 'isOpenDay');
        self::assertSame($expected, $method->invoke($controller, new \DateTimeImmutable($date), $days));
    }
}
