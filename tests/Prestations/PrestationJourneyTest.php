<?php

namespace App\Tests\Prestations;

use App\Command\GenerateSitemapCommand;
use App\Entity\Appointment;
use App\Entity\AppointmentType;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DomCrawler\Crawler;

final class PrestationJourneyTest extends TestCase
{
    private TestKernel $kernel;
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private int $typeId;
    private const PASSWORD = 'Synthetic-Only-8z!V4q#L2';

    protected function setUp(): void
    {
        require_once __DIR__.'/bootstrap.php';
        $this->kernel = new TestKernel('test', true);
        $this->kernel->boot();
        $container = $this->kernel->getContainer()->get('test.service_container');
        $this->em = $container->get('doctrine')->getManager();
        $params = $this->em->getConnection()->getParams();
        // Mandatory guard BEFORE any schema/data write. No development DB is used.
        self::assertSame('pdo_sqlite', $params['driver']);
        self::assertTrue($params['memory'] ?? false);
        self::assertEmpty($params['path'] ?? null);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $other = (new AppointmentType())->setName('Autre prestation')->setSlug('autre')->setDuration(30)->setPrice(1000);
        $this->em->persist($other);
        $this->em->flush();
        $type = (new AppointmentType())->setName('Prestation synthétique')->setSlug('prestation-test')
            ->setDuration(75)->setPrice(12300)->setMinAge(18)->setParticipants(1)
            ->setIntroduction('Introduction administrée de test.')->setDescription('<p>Description administrée.</p>');
        $type->setImageName('synthetic-image.webp');
        $this->em->persist($type);
        $this->em->flush();
        $this->typeId = $type->getId();
        CaptureAuthMailer::$codes = [];
        $this->client = new KernelBrowser($this->kernel);
        $this->client->disableReboot();
    }

    protected function tearDown(): void
    {
        self::assertSame(0, $this->em->getRepository(Appointment::class)->count([]), 'No reservation may be created.');
        $this->em->getConnection()->close();
        $this->kernel->shutdown();
    }

    private function detail(): string { return '/rendez-vous/types/'.$this->typeId; }
    private function booking(): string { return '/rendez-vous/type/'.$this->typeId.'/form'; }

    private function assertStatus(int $status): void
    {
        self::assertSame($status, $this->client->getResponse()->getStatusCode(), substr($this->client->getResponse()->getContent(), 0, 500));
    }

    public function testPublicPageAjaxAndRealLinks(): void
    {
        $page = $this->client->request('GET', $this->detail().'?campaign=test');
        $this->assertStatus(200);
        self::assertSame(1, $page->filter('html head')->count());
        self::assertStringContainsString('Prestation synthétique', $page->filter('title')->text());
        self::assertSame('Introduction administrée de test.', $page->filter('meta[name="description"]')->attr('content'));
        self::assertSame('http://localhost'.$this->detail(), $page->filter('link[rel="canonical"]')->attr('href'));
        self::assertSame('/images/appointments/synthetic-image.webp', $page->filter('img[alt="Prestation synthétique"]')->attr('src'));
        self::assertSame($this->booking(), $page->selectLink('Réserver')->attr('href'));
        self::assertContains('X-Requested-With', $this->client->getResponse()->getVary());
        $fragment = $this->client->xmlHttpRequest('GET', $this->detail());
        $this->assertStatus(200);
        self::assertStringNotContainsString('<html', $this->client->getResponse()->getContent());
        self::assertSame($page->filter('.skhvxm')->html(), $fragment->filter('.skhvxm')->html());
        self::assertContains('X-Requested-With', $this->client->getResponse()->getVary());
        $catalogue = $this->client->request('GET', '/rendez-vous/types');
        self::assertSame($this->detail(), $catalogue->selectLink('Voir le détail : Prestation synthétique')->attr('href'));
        self::assertSame('click->appointment-type#showDetail', $catalogue->selectLink('Voir le détail : Prestation synthétique')->attr('data-action'));
        self::assertContains('X-Requested-With', $this->client->getResponse()->getVary());
    }

    public function testMissingTypeReturns404ForBothRepresentations(): void
    {
        $this->client->request('GET', '/rendez-vous/types/999999');
        $this->assertStatus(404);
        $this->client->xmlHttpRequest('GET', '/rendez-vous/types/999999');
        $this->assertStatus(404);
    }

    private function enterBooking(): Crawler
    {
        $this->client->request('GET', $this->booking());
        return $this->followRedirectTo('/login');
    }

    private function assertRedirectTo(string $path): void
    {
        $this->assertStatus(302);
        // Check the whole Location, not just its suffix: an external host can
        // also end with the expected booking path. Assert BEFORE following it.
        self::assertContains($this->client->getResponse()->headers->get('Location'), [$path, 'http://localhost'.$path]);
    }

    private function followRedirectTo(string $path): Crawler
    {
        $this->assertRedirectTo($path);
        return $this->client->followRedirect();
    }

    private function reachTwoFactor(string $email): Crawler
    {
        $this->assertStatus(302);
        // Scheb starts the challenge when following the initial login redirect.
        if (in_array($this->client->getResponse()->headers->get('Location'), [$this->booking(), 'http://localhost'.$this->booking()], true)) {
            $this->followRedirectTo($this->booking());
        }
        $page = $this->followRedirectTo('/2fa');
        $this->assertStatus(200);
        self::assertSame(1, $page->filter('form input[name="_auth_code"]')->count());
        self::assertSame(0, $page->filter('#calendar')->count());
        self::assertArrayHasKey($email, CaptureAuthMailer::$codes);
        return $page;
    }

    private function finishTwoFactor(string $email): void
    {
        $page = $this->reachTwoFactor($email);
        // From this point, only submit the displayed forms and follow their
        // redirects. Never revisit booking or re-seed the session destination.
        $this->client->submit($page->filter('form')->form(['_auth_code' => 'wrong-code']));
        $page = $this->followRedirectTo('/2fa');
        self::assertSame(1, $page->filter('.alert-danger')->count());
        $this->client->submit($page->filter('form')->form(['_auth_code' => CaptureAuthMailer::$codes[$email]]));
        $this->assertCalendarAfterTwoFactor();
    }

    private function assertCalendarAfterTwoFactor(): void
    {
        $form = $this->followRedirectTo($this->booking());
        $this->assertStatus(200);
        self::assertSame(1, $form->filter('head title')->count());
        self::assertSame((string) $this->typeId, $form->filter('#calendar')->attr('data-calendar-type-id-value'));
        self::assertSame($this->booking(), $form->filter('form')->attr('action'));
        $this->client->xmlHttpRequest('GET', $this->booking());
        $this->assertStatus(200);
        self::assertStringNotContainsString('<html', $this->client->getResponse()->getContent());
    }

    public function testLoginErrorThenEmailVerificationReturnsToSelectedType(): void
    {
        $user = $this->persistLoginUser('existing@example.test');
        $page = $this->enterBooking();
        $this->client->submit($page->filter('#login_form')->form(['_username' => $user->getEmail(), '_password' => 'incorrect']));
        $page = $this->followRedirectTo('/login');
        self::assertSame(1, $page->filter('.alert-danger')->count());
        $this->client->submit($page->filter('#login_form')->form(['_username' => $user->getEmail(), '_password' => self::PASSWORD]));
        $this->finishTwoFactor('existing@example.test');
    }

    public function testRegistrationErrorThenEmailVerificationReturnsToSelectedType(): void
    {
        $page = $this->enterBooking();
        $page = $this->client->click($page->selectLink("M'inscrire")->link());
        $values = ['registration_form[email]' => 'registered@example.test', 'registration_form[plainPassword]' => self::PASSWORD,
            'registration_form[firstname]' => 'Camille', 'registration_form[lastname]' => 'Synthetique',
            'registration_form[adress]' => '1 rue de test', 'registration_form[postalCode]' => '75001',
            'registration_form[city]' => 'Paris', 'registration_form[phone]' => '0612345678', 'registration_form[agreeTerms]' => '1'];
        $page = $this->client->submit($page->filter('#registration_form')->form(array_replace($values, ['registration_form[plainPassword]' => 'weak'])));
        self::assertSame(0, $this->em->getRepository(User::class)->count([]));
        self::assertSame(1, $page->filter('#registration_form')->count());
        $this->client->submit($page->filter('#registration_form')->form($values));
        self::assertSame(1, $this->em->getRepository(User::class)->count([]));
        $this->finishTwoFactor('registered@example.test');
    }

    public function testBookingRemainsInaccessibleBeforeEmailCodeValidation(): void
    {
        $user = $this->persistLoginUser('blocked@example.test');
        $page = $this->enterBooking();
        $this->client->submit($page->filter('#login_form')->form(['_username' => $user->getEmail(), '_password' => self::PASSWORD]));
        $this->reachTwoFactor($user->getEmail());

        // This intentional access probe is independent of return-path tests:
        // it can cause the firewall to save the destination again.
        $page = $this->client->request('GET', $this->booking());
        self::assertSame(0, $page->filter('#calendar')->count());
        $page = $this->followRedirectTo('/2fa');
        $this->assertStatus(200);
        self::assertSame(1, $page->filter('input[name="_auth_code"]')->count());
        self::assertSame(0, $page->filter('#calendar')->count());
    }

    public static function externalLoginTargets(): iterable
    {
        // target is the application query parameter; _target_path is consumed
        // by the installed Symfony form-login success handler (GET or POST).
        foreach (['target', '_target_path'] as $parameter) {
            foreach (['https://evil.test', '//evil.test'] as $origin) {
                yield $parameter.' GET '.$origin => [$parameter, 'GET', $origin];
            }
        }
        yield '_target_path POST' => ['_target_path', 'POST', 'https://evil.test'];
    }

    #[DataProvider('externalLoginTargets')]
    public function testExternalLoginTargetCannotOverrideBookingThroughFirewall(string $parameter, string $method, string $origin): void
    {
        $user = $this->persistLoginUser('target@example.test');
        $page = $this->enterBooking();
        // Deliberately use the same path on the hostile host to catch assertions
        // that would wrongly accept an external URL based on its suffix alone.
        $external = $origin.$this->booking();
        if ($method === 'GET') {
            $page = $this->client->request('GET', '/login?'.http_build_query([$parameter => $external]));
            $this->assertStatus(200);
        }
        $form = $page->filter('#login_form')->form(['_username' => $user->getEmail(), '_password' => self::PASSWORD]);
        if ($method === 'POST') {
            $this->client->request($form->getMethod(), $form->getUri(), array_replace($form->getPhpValues(), [$parameter => $external]));
        } else {
            $this->client->submit($form);
        }
        $this->finishTwoFactor($user->getEmail());
    }

    public function testUnrecognizedTwoFactorReturnFieldsCannotOverrideBooking(): void
    {
        $user = $this->persistLoginUser('two-factor-target@example.test');
        $page = $this->enterBooking();
        $this->client->submit($page->filter('#login_form')->form(['_username' => $user->getEmail(), '_password' => self::PASSWORD]));
        $page = $this->reachTwoFactor($user->getEmail());
        // Installed Scheb 7.13.1 reads the session target, not an HTTP return
        // parameter. These extra fields must therefore be ignored, not consumed.
        self::assertSame(0, $page->filter('input[name="target"], input[name="_target_path"]')->count());
        $form = $page->filter('form')->form(['_auth_code' => CaptureAuthMailer::$codes[$user->getEmail()]]);
        $this->client->request($form->getMethod(), $form->getUri(), array_replace($form->getPhpValues(), [
            'target' => 'https://evil.test'.$this->booking(),
            '_target_path' => 'https://evil.test'.$this->booking(),
        ]));
        $this->assertCalendarAfterTwoFactor();
    }

    private function persistLoginUser(string $email): User
    {
        $user = $this->syntheticUser($email);
        $hasher = $this->kernel->getContainer()->get('test.service_container')->get('security.user_password_hasher');
        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    public function testSitemapUsesExistingRoutesAndTemporaryOutputOnly(): void
    {
        $existing = $this->kernel->getProjectDir().'/public/sitemap.xml';
        $before = is_file($existing) ? hash_file('sha256', $existing) : null;
        $destination = tempnam($this->kernel->getProjectDir().'/var/prestations-tests', 'sitemap-');
        try {
            $container = $this->kernel->getContainer()->get('test.service_container');
            $router = $container->get('router');
            self::assertNull($router->getRouteCollection()->get('app_numerologie'));
            $tester = new CommandTester(new GenerateSitemapCommand($this->em, $router, $this->kernel, $router, 'http://localhost'));
            self::assertSame(0, $tester->execute(['--output' => $destination]));
            $xml = simplexml_load_file($destination);
            self::assertNotFalse($xml);
            $locations = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));
            self::assertContains('http://localhost'.$this->detail(), $locations);
            foreach ($locations as $url) {
                self::assertArrayHasKey('_route', $router->match(parse_url($url, PHP_URL_PATH)));
            }
            self::assertSame($before, is_file($existing) ? hash_file('sha256', $existing) : null);
        } finally {
            unlink($destination);
        }
    }

    public function testAgeAndPrerequisiteStillRejectAnIneligibleBooking(): void
    {
        $user = $this->syntheticUser('eligibility@example.test')->setPassword('unused');
        $this->em->persist($user);
        $this->em->flush();
        $this->client->loginUser($user);
        $page = $this->client->request('GET', $this->booking());
        $this->assertStatus(200);
        $values = ['appointment_form[startAt]' => (new \DateTimeImmutable('+4 days'))->format('Y-m-d').'T10:00',
            'appointment_form[evaluatedPerson][firstname]' => 'Camille', 'appointment_form[evaluatedPerson][lastname]' => 'Synthetique',
            'appointment_form[evaluatedPerson][patronyms]' => 'Camille Synthetique',
            'appointment_form[evaluatedPerson][birthdate]' => (new \DateTimeImmutable('-10 years'))->format('Y-m-d')];
        $page = $this->client->submit($page->filter('form')->form($values));
        self::assertStringContainsString("L'âge minimum", $page->text());
        $type = $this->em->find(AppointmentType::class, $this->typeId);
        $type->setPrerequisite($this->em->getRepository(AppointmentType::class)->findOneBy(['slug' => 'autre']));
        $this->em->flush();
        $values['appointment_form[evaluatedPerson][birthdate]'] = '1990-01-01';
        $page = $this->client->submit($page->filter('form')->form($values));
        self::assertStringContainsString("vous devez d'abord effectuer", $page->text());
    }

    private function syntheticUser(string $email): User
    {
        $user = (new User())->setEmail($email)->setFirstname('Camille')->setLastname('Synthetique')
            ->setAdress('1 rue de test')->setPostalCode('75001')->setCity('Paris')
            ->setPhone(\libphonenumber\PhoneNumberUtil::getInstance()->parse('0612345678', 'FR'));
        $avatar = new \App\Entity\Avatar($user);
        $avatar->setImageName('synthetic-avatar.png');
        $user->setAvatar($avatar);
        return $user;
    }
}
