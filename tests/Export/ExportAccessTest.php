<?php
namespace App\Tests\Export;

use App\Controller\Admin\AppointmentCrudController;
use App\Entity\Appointment;
use App\Entity\AppointmentType;
use App\Entity\Avatar;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Tests\Prestations\CaptureAuthMailer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class ExportAccessTest extends TestCase
{
    private ExportKernel $kernel;
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $admin;
    private User $ordinary;
    private int $id;
    private const PASSWORD='Synthetic-Only-8z!V4q#L2';

    protected function setUp(): void
    {
        ExportIoFaults::reset();
        $this->kernel=new ExportKernel('test',true);$this->kernel->boot();
        CaptureAuthMailer::$codes=[];
        $container=$this->kernel->getContainer()->get('test.service_container');
        $this->em=$container->get('doctrine')->getManager();
        $params=$this->em->getConnection()->getParams();
        self::assertSame('pdo_sqlite',$params['driver']);self::assertTrue($params['memory']??false);self::assertEmpty($params['path']??null);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $this->ordinary=$this->user('ordinary@example.test',[]);
        $this->admin=$this->user('admin@example.test',['ROLE_ADMIN']);
        $type=(new AppointmentType())->setName('Évaluation synthétique')->setSlug('synthetic-export')->setPrice(15000)->setDuration(60);
        $appointment=(new Appointment())->setUser($this->ordinary)->setType($type)->setStatus(AppointmentStatus::CONFIRMED)
            ->setStartAt(new \DateTimeImmutable('2026-07-15 12:00:00 UTC'))->setEndAt(new \DateTimeImmutable('2026-07-15 13:00:00 UTC'));
        $this->em->persist($type);$this->em->persist($appointment);$this->em->flush();$this->id=$appointment->getId();
        $this->client=new KernelBrowser($this->kernel);$this->client->disableReboot();
    }

    public static function actions(): iterable
    {
        foreach (['exportCsv','exportIcs','exportCsvAll','exportIcsAll'] as $action) yield $action=>[$action];
    }
    public static function errors(): iterable { yield 'CSV'=>['exportCsv'];yield 'ICS'=>['exportIcs']; }

    #[DataProvider('actions')]
    public function testAnonymousIsRedirectedWithoutMarking(string $action): void
    {
        $this->client->request('GET',$this->url($action));$this->assertRedirect('/login');$this->assertMarked(false);
    }

    #[DataProvider('actions')]
    public function testOrdinaryUserIsForbiddenWithoutMarking(string $action): void
    {
        $this->client->loginUser($this->ordinary);$this->client->request('GET',$this->url($action));
        self::assertSame(403,$this->client->getResponse()->getStatusCode());$this->assertMarked(false);
    }

    #[DataProvider('actions')]
    public function testAuthenticatedAdminReceivesPreparedExport(string $action): void
    {
        $this->client->loginUser($this->admin);$this->client->request('GET',$this->url($action));
        $response=$this->client->getResponse();
        $body=$this->client->getInternalResponse()->getContent();
        self::assertSame(200,$response->getStatusCode());
        self::assertStringContainsString('attachment; filename="appointments_',$response->headers->get('Content-Disposition'));
        self::assertStringContainsString(str_contains($action,'Csv')?'Subject':'BEGIN:VCALENDAR',$body);
        self::assertStringContainsString('Évaluation synthétique',$body);
        self::assertTrue(ExportIoFaults::allClosed());$this->assertMarked(!str_ends_with($action,'All'));
        self::assertSame([],CaptureAuthMailer::$codes);
    }

    #[DataProvider('errors')]
    public function testGenerationErrorUsesErrorResponseBeforeDownload(string $action): void
    {
        $this->client->loginUser($this->admin);ExportIoFaults::reset('open');
        $this->client->request('GET',$this->url($action));
        $response=$this->client->getResponse();
        self::assertSame(500,$response->getStatusCode());
        self::assertFalse($response->headers->has('Content-Disposition'));
        self::assertStringContainsString('text/html',$response->headers->get('Content-Type'));
        $this->assertMarked(false);self::assertTrue(ExportIoFaults::allClosed());
    }

    public function testRealTwoFactorChallengeBlocksAllExportsUntilCompleted(): void
    {
        $url=$this->url('exportCsv');$this->client->request('GET',$url);$this->assertRedirect('/login');
        $page=$this->client->followRedirect();
        $this->client->submit($page->filter('#login_form')->form(['_username'=>$this->admin->getEmail(),'_password'=>self::PASSWORD]));
        for ($i=0;$i<4 && $this->client->getResponse()->isRedirect();++$i) $page=$this->client->followRedirect();
        self::assertSame(1,$page->filter('input[name="_auth_code"]')->count());
        foreach (self::actions() as [$action]) {
            $this->client->request('GET',$this->url($action));$this->assertRedirect('/2fa');$this->assertMarked(false);
        }
        $this->client->request('GET',$url);$page=$this->client->followRedirect();
        self::assertArrayHasKey($this->admin->getEmail(),CaptureAuthMailer::$codes);
        $this->client->submit($page->filter('form')->form(['_auth_code'=>CaptureAuthMailer::$codes[$this->admin->getEmail()]]));
        self::assertSame(302,$this->client->getResponse()->getStatusCode());
        $this->client->request('GET',$url);self::assertSame(200,$this->client->getResponse()->getStatusCode());
        self::assertStringContainsString('Subject',$this->client->getInternalResponse()->getContent());$this->assertMarked(true);
    }

    private function url(string $action): string
    {
        return $this->kernel->getContainer()->get('test.service_container')->get(AdminUrlGenerator::class)
            ->unsetAll()->setController(AppointmentCrudController::class)->setAction($action)->generateUrl();
    }
    private function assertMarked(bool $expected): void
    {
        $this->em->clear();self::assertSame($expected,$this->em->find(Appointment::class,$this->id)->isSent());
    }
    private function assertRedirect(string $path): void
    {
        self::assertSame(302,$this->client->getResponse()->getStatusCode());
        self::assertSame($path,parse_url($this->client->getResponse()->headers->get('Location'),PHP_URL_PATH));
    }
    private function user(string $email,array $roles): User
    {
        $user=(new User())->setEmail($email)->setRoles($roles)->setFirstname('Camille')->setLastname('Synthetic')
            ->setAdress('1 rue de test')->setPostalCode('75001')->setCity('Paris')
            ->setPhone(\libphonenumber\PhoneNumberUtil::getInstance()->parse('0612345678','FR'));
        $avatar=new Avatar($user);$avatar->setImageName('synthetic-avatar.png');$user->setAvatar($avatar);
        $hasher=$this->kernel->getContainer()->get('test.service_container')->get('security.user_password_hasher');
        $user->setPassword($hasher->hashPassword($user,self::PASSWORD));$this->em->persist($user);return $user;
    }
    protected function tearDown(): void
    {
        foreach (ExportIoFaults::$resources as $r) if (is_resource($r)) \fclose($r);
        ExportIoFaults::reset();$this->em->getConnection()->close();$this->kernel->shutdown();
    }
}
