<?php
namespace App\Tests\Export;

use App\Controller\Admin\AppointmentCrudController;
use App\Entity\Appointment;
use App\Entity\EvaluatedPerson;
use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use App\Service\AppointmentExportService;
use App\Tests\Payment\PaymentTestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportFlowTest extends PaymentTestCase
{
    private AppointmentRepository $repository;
    private AppointmentCrudController $controller;
    private array $ids;
    private const TITLE = 'Été, "couple" \\ chemin';

    protected function setUp(): void
    {
        parent::setUp();
        ExportIoFaults::reset();
        $first=$this->appointment->setStatus(AppointmentStatus::CONFIRMED);
        $first->getType()->setName(self::TITLE)->setSlug('analyse-couple');
        $first->getUser()->setFirstname('Élodie')->setLastname('DUPONT');
        $person=(new EvaluatedPerson())->setFirstname('Zoé')->setLastname('Détail, "cité"')
            ->setPatronyms('a\\b')->setBirthdate(new \DateTime('1990-04-03'));
        $first->setEvaluatedPerson($person)->setPartner((new EvaluatedPerson())->setFirstname('René')->setLastname('Martin'));
        $second=$this->newAppointment()->setStatus(AppointmentStatus::CONFIRMED);
        $pending=$this->newAppointment();
        $canceled=$this->newAppointment()->setStatus(AppointmentStatus::CANCELED);
        $sent=$this->newAppointment()->setStatus(AppointmentStatus::CONFIRMED)->setIsSent(true);
        foreach ([$first,$second,$pending,$canceled,$sent] as $a) {
            $a->setStartAt(new \DateTimeImmutable('2026-07-15 12:30:00 UTC'))->setEndAt(new \DateTimeImmutable('2026-07-15 14:00:00 UTC'));
        }
        $this->em->flush();
        $this->ids=array_map(static fn(Appointment $a):int=>$a->getId(),[$first,$second,$pending,$canceled,$sent]);
        $this->wireRepository();
    }

    private function wireRepository(): void
    {
        $registry=$this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);
        $this->repository=new AppointmentRepository($registry);
        $url=(new \ReflectionClass(AdminUrlGenerator::class))->newInstanceWithoutConstructor();
        $this->controller=new AppointmentCrudController($this->repository,$url);
    }

    public static function formats(): iterable { yield 'CSV'=>['Csv']; yield 'ICS'=>['Ics']; }
    public static function actions(): iterable
    {
        foreach (['Csv','Ics'] as $format) foreach ([false,true] as $all) yield $format.($all?'All':'')=>[$format,$all];
    }

    #[DataProvider('actions')]
    public function testSuccessContentSelectionAndNextExport(string $format,bool $all): void
    {
        $action='export'.$format.($all?'All':'');
        ob_start();
        try {$response=$this->controller->$action(new AppointmentExportService());$output=ob_get_contents();}
        finally {ob_end_clean();}
        self::assertSame('',$output,'No HTTP content before generation and persistence finish.');
        self::assertNotInstanceOf(StreamedResponse::class,$response);
        self::assertSame(200,$response->getStatusCode());
        self::assertTrue(ExportIoFaults::allClosed());
        $body=$response->getContent();
        $this->assertState($all?[0,0,0,0,1]:[1,1,0,0,1]);
        self::assertSame($all?2:0,count($this->repository->findForCalendarExport()));
        self::assertCount(3,$this->repository->findForCalendarExport(false,false));
        self::assertCount($all?3:1,$this->repository->findForCalendarExport(true,true));
        self::assertMatchesRegularExpression('/^attachment; filename="appointments_\d{8}_\d{6}_'.($all?'all':'confirmed').'\.'.strtolower($format).'"$/',$response->headers->get('Content-Disposition'));
        if ($format==='Csv') {
            self::assertSame('text/csv; charset=UTF-8',$response->headers->get('Content-Type'));
            $lines=explode("\n",trim($body));
            self::assertCount($all?4:3,$lines);
            self::assertSame(['Subject','Start Date','Start Time','End Date','End Time','Description'],str_getcsv($lines[0],',','"','\\'));
            $row=str_getcsv($lines[1],',','"','\\');
            self::assertSame([self::TITLE,'07/15/2026','14:30','07/15/2026','16:00'],array_slice($row,0,5));
            self::assertStringContainsString('Élodie DUPONT',$row[5]);
            self::assertStringContainsString('Zoé Détail, "cité" (a\\b), naissance 03/04/1990',$row[5]);
            self::assertStringContainsString('Partenaire : René Martin',$row[5]);
            // Explicit arguments must produce exactly PHP's historical CSV escaping.
            $legacy=\fopen('php://memory','w+');
            set_error_handler(static fn(int $severity):bool=>$severity===E_DEPRECATED);
            try {\fputcsv($legacy,$row);} finally {restore_error_handler();}
            \rewind($legacy);$expected=\stream_get_contents($legacy);\fclose($legacy);
            self::assertSame($expected,$lines[1]."\n");
        } else {
            self::assertSame('text/calendar; charset=UTF-8',$response->headers->get('Content-Type'));
            self::assertSame($all?3:2,substr_count($body,'BEGIN:VEVENT'));
            self::assertStringContainsString('UID:appointment-'.$this->ids[0].'@luniversdesnombres.com',$body);
            self::assertStringContainsString("DTSTART:20260715T123000Z\r\nDTEND:20260715T140000Z",$body);
            self::assertStringContainsString('SUMMARY:Été\\, "couple" \\\\ chemin',$body);
            self::assertStringContainsString('Partenaire : René Martin',$body);
            self::assertStringEndsWith("END:VCALENDAR\r\n",$body);
        }
        self::assertSame(0,$this->mailCount());
    }

    #[DataProvider('formats')]
    public function testEmptySelectionProducesValidDocumentWithoutWriting(string $format): void
    {
        foreach ($this->repository->findForCalendarExport() as $a) $a->setIsSent(true);
        $this->em->flush();$this->em->clear();
        $events=new class { public int $flushes=0; public function onFlush():void {++$this->flushes;} };
        $this->em->getEventManager()->addEventListener(['onFlush'],$events);
        $action='export'.$format;$response=$this->controller->$action(new AppointmentExportService());
        self::assertNotInstanceOf(StreamedResponse::class,$response);
        self::assertSame(0,$events->flushes);
        self::assertTrue(ExportIoFaults::allClosed());
        self::assertStringContainsString($format==='Csv'?'Subject':'END:VCALENDAR',$response->getContent());
    }

    public static function faults(): iterable
    {
        foreach (['Csv','Ics'] as $format) foreach (['open','flush','tell','rewind','read','read_short','close','close_throw'] as $mode) yield $format.' '.$mode=>[$format,$mode,false];
        foreach (['csv_header','csv_row','csv_count','interrupt','interrupt_cleanup'] as $mode) yield $mode=>['Csv',$mode,false];
        foreach (['write','write_zero'] as $mode) yield $mode=>['Ics',$mode,false];
        yield 'All CSV error'=>['Csv','csv_row',true];
        yield 'All ICS error'=>['Ics','write',true];
    }

    #[DataProvider('faults')]
    public function testGenerationFailureKeepsSelectionAndReleasesResources(string $format,string $mode,bool $all): void
    {
        ExportIoFaults::reset($mode);$error=null;$response=null;
        $action='export'.$format.($all?'All':'');
        ob_start();
        try {$response=$this->controller->$action(new AppointmentExportService());}
        catch (\Throwable $e) {$error=$e;}
        finally {$output=ob_get_clean();}
        $this->assertState([0,0,0,0,1]);
        self::assertNull($response);
        self::assertInstanceOf(\RuntimeException::class,$error);
        if (str_starts_with($mode,'interrupt')) self::assertSame('interruption synthétique',$error->getMessage());
        self::assertSame('',$output);
        self::assertTrue(ExportIoFaults::allClosed());
        self::assertCount(2,$this->repository->findForCalendarExport());
        self::assertSame(0,$this->mailCount());
    }

    public function testPartialIcsWritesAreRetried(): void
    {
        $expected=(new AppointmentExportService())->streamIcs($this->repository->findForCalendarExport())->getContent();
        ExportIoFaults::reset('partial');
        $response=$this->controller->exportIcs(new AppointmentExportService());
        self::assertNotInstanceOf(StreamedResponse::class,$response);
        self::assertSame(2,substr_count($response->getContent(),'END:VEVENT'));
        self::assertStringEndsWith("END:VCALENDAR\r\n",$response->getContent());
        self::assertSame(preg_replace('/DTSTAMP:\d{8}T\d{6}Z/','DTSTAMP:NOW',$expected),preg_replace('/DTSTAMP:\d{8}T\d{6}Z/','DTSTAMP:NOW',$response->getContent()));
        $this->assertState([1,1,0,0,1]);self::assertTrue(ExportIoFaults::allClosed());
    }

    public function testCsvPreservesHistoricalQuoteAndBackslashBytes(): void
    {
        foreach (['virgule, ici','"guillemets"','antislash\\','\\"ensemble','deux\\\\"fin'] as $title) {
            $this->appointment->getType()->setName($title);
            $response=(new AppointmentExportService())->streamCsv([$this->appointment]);
            self::assertNotInstanceOf(StreamedResponse::class,$response);
            $description='Client : Élodie DUPONT | Personne : Zoé Détail, "cité" (a\\b), naissance 03/04/1990 | Partenaire : René Martin, naissance —';
            $legacy=\fopen('php://memory','w+');
            set_error_handler(static fn(int $severity):bool=>$severity===E_DEPRECATED);
            try {
                \fputcsv($legacy,['Subject','Start Date','Start Time','End Date','End Time','Description']);
                \fputcsv($legacy,[$title,'07/15/2026','14:30','07/15/2026','16:00',$description]);
            } finally {restore_error_handler();}
            \rewind($legacy);$expected=\stream_get_contents($legacy);\fclose($legacy);
            self::assertSame($expected,$response->getContent());self::assertTrue(ExportIoFaults::allClosed());
        }
    }

    public function testIcsDoesNotSilentlySkipIncompleteSelectedElement(): void
    {
        $a=$this->em->find(Appointment::class,$this->ids[1]);
        // Deliberately incomplete in-memory object, never persisted to a NOT NULL column.
        (new \ReflectionProperty(Appointment::class,'startAt'))->setValue($a,null);
        $error=null;
        try {$this->controller->exportIcs(new AppointmentExportService());} catch (\Throwable $e) {$error=$e;}
        self::assertInstanceOf(\UnexpectedValueException::class,$error);
        self::assertTrue(ExportIoFaults::allClosed());
        $this->assertState([0,0,0,0,1]);self::assertCount(2,$this->repository->findForCalendarExport());
    }

    #[DataProvider('formats')]
    public function testFlushFailureRollsBackBothUpdatesAndClosesManager(string $format): void
    {
        $selected=$this->repository->findForCalendarExport();
        $listener=new class {
            public int $updated=0;
            public function postUpdate(PostUpdateEventArgs $args):void {
                if ($args->getObject() instanceof Appointment && ++$this->updated===2) throw new \RuntimeException('flush après deux UPDATE');
            }
        };
        $this->em->getEventManager()->addEventListener(['postUpdate'],$listener);
        $response=null;$error=null;$action='export'.$format;
        try {$response=$this->controller->$action(new AppointmentExportService());} catch (\Throwable $e) {$error=$e;}
        self::assertNull($response);self::assertSame('flush après deux UPDATE',$error?->getMessage());
        self::assertSame(2,$listener->updated);self::assertFalse($this->em->isOpen());
        self::assertFalse($this->em->getConnection()->isTransactionActive());
        self::assertTrue($selected[0]->isSent());self::assertTrue($selected[1]->isSent(),'Detached objects retain flags; discard them.');
        self::assertTrue(ExportIoFaults::allClosed());
        $this->em=new EntityManager($this->em->getConnection(),$this->em->getConfiguration());
        $this->wireRepository();$this->assertState([0,0,0,0,1]);
        self::assertCount(2,$this->repository->findForCalendarExport());
    }

    private function assertState(array $expected): void
    {
        $this->em->clear();
        foreach ($this->ids as $i=>$id) self::assertSame((bool)$expected[$i],$this->em->find(Appointment::class,$id)->isSent());
    }

    protected function tearDown(): void
    {
        // Test harness fallback only; assertions above verify production cleanup first.
        foreach (ExportIoFaults::$resources as $r) if (is_resource($r)) \fclose($r);
        ExportIoFaults::reset();parent::tearDown();
    }
}
