<?php

namespace App\Tests\Phone;

use App\Entity\Avatar;
use App\Entity\Company;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Security\Core\User\UserInterface;

final class PhoneFlowTest extends TestCase
{
    private const REQUIRED = 'Merci de renseigner votre numéro de téléphone.';
    private const INVALID = "Cette valeur n'est pas un numéro de téléphone valide.";
    private const PASSWORD = 'Diag!9tX#48rQ@2mYp7';
    private PhoneKernel $kernel;
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private ?int $userId = null;
    private ?int $avatarId = null;
    private array $before = [];

    protected function setUp(): void
    {
        require_once __DIR__.'/bootstrap.php';
        $this->kernel = new PhoneKernel('test', true);
        $this->kernel->boot();
        $container = $this->kernel->getContainer()->get('test.service_container');
        $this->em = $container->get('doctrine')->getManager();
        $params = $this->em->getConnection()->getParams();
        self::assertSame('pdo_sqlite', $params['driver']);
        self::assertTrue($params['memory'] ?? false);
        self::assertEmpty($params['path'] ?? null);
        // Guard the destination BEFORE schema/fixtures, not after a connection has been used.
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $company = (new Company())->setName('Entreprise synthétique')->setSlug('synthetic')
            ->setAdress('1 rue fictive')->setPostalCode('75001')->setCity('Paris')->setEmail('company@example.test')
            ->setPhone(PhoneNumberUtil::getInstance()->parse('0612345678', 'FR'))
            ->setType('TEST')->setSiren('123456789')->setUrl('https://example.test')->setManager('Camille Fictif');
        $this->em->persist($company);
        $this->em->flush();
        $realSecurity = $container->get(Security::class);
        $observer = $this->createStub(Security::class);
        $observer->method('login')->willReturnCallback(static function (UserInterface $user, ?string $authenticator = null, ?string $firewall = null, array $badges = [], array $attributes = []) use ($realSecurity) {
            PhoneEffects::$calls[] = 'security.login';
            return $realSecurity->login($user, $authenticator, $firewall, $badges, $attributes);
        });
        $container->set('test.phone_security', $observer);
        $container->set('http_client', new MockHttpClient(static function () {
            PhoneEffects::$calls[] = 'http.request';
            throw new \LogicException('External HTTP forbidden in phone tests.');
        }));
        $this->client = new KernelBrowser($this->kernel);
        $this->client->disableReboot();
        $this->client->catchExceptions(false);
        PhoneEffects::reset();
    }

    protected function tearDown(): void
    {
        if (isset($this->em)) $this->em->getConnection()->close();
        if (isset($this->kernel)) $this->kernel->shutdown();
        PhoneEffects::reset();
    }

    public static function invalidPhones(): iterable
    {
        foreach (['registration', 'profile'] as $journey) {
            yield "$journey missing" => [$journey, null, true];
            yield "$journey empty" => [$journey, '', true];
            yield "$journey spaces" => [$journey, '   ', true];
            yield "$journey malformed" => [$journey, 'not-a-phone', false];
            yield "$journey invalid" => [$journey, '0123', false];
        }
    }

    #[DataProvider('invalidPhones')]
    public function testRejectedPhoneHasFieldErrorAndNoSideEffect(string $journey, ?string $phone, bool $missing): void
    {
        $token = $this->open($journey);
        $page = $this->submit($journey, $this->payload($journey, $phone) + ['_token' => $token]);
        self::assertSame(422, $this->client->getResponse()->getStatusCode());
        $this->assertFieldError($page, $journey, 'phone', $missing ? self::REQUIRED : self::INVALID);
        if (!$missing) self::assertStringNotContainsString(self::REQUIRED, $this->field($page, $journey, 'phone')->text());
        // The managed profile can be mutated by binding even when nothing is persisted.
        if ($journey === 'profile' && $missing) {
            self::assertNull($this->em->find(User::class, $this->userId)->getPhone());
        }
        $this->assertNoEffectAndReload();
    }

    public static function validPhones(): iterable
    {
        foreach (['registration', 'profile'] as $journey) {
            yield "$journey national" => [$journey, '06 98 76 54 32'];
            yield "$journey international" => [$journey, '+33 6 98 76 54 32'];
        }
    }

    #[DataProvider('validPhones')]
    public function testValidNumberIsPersistedAndRedirects(string $journey, string $phone): void
    {
        $token = $this->open($journey);
        $this->submit($journey, $this->payload($journey, $phone) + ['_token' => $token]);
        self::assertSame(302, $this->client->getResponse()->getStatusCode());
        $path = parse_url($this->client->getResponse()->headers->get('Location'), PHP_URL_PATH);
        self::assertSame($journey === 'profile' ? '/profile' : '/login/success', $path);
        self::assertContains('flush', PhoneEffects::$calls, 'The observer must see successful real writes.');
        if ($journey === 'registration') {
            self::assertSame(1, array_count_values(PhoneEffects::$calls)['avatar.create'] ?? 0);
            self::assertSame(1, array_count_values(PhoneEffects::$calls)['security.login'] ?? 0);
            self::assertContains('persist:'.User::class, PhoneEffects::$calls);
        } else {
            self::assertSame(['flush'], PhoneEffects::$calls);
        }
        self::assertNotContains('http.request', PhoneEffects::$calls);
        $this->em->clear();
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'client@example.test']);
        self::assertNotNull($user);
        self::assertSame('+33698765432', PhoneNumberUtil::getInstance()->format($user->getPhone(), PhoneNumberFormat::E164));
        self::assertSame('Camille', $user->getFirstname());
        self::assertSame('FICTIF', $user->getLastname());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertNotNull($user->getAvatar());
        if ($journey === 'profile') {
            self::assertSame($this->avatarId, $user->getAvatar()->getId());
            self::assertSame($this->before['avatar'], $this->rows()['avatar']);
            self::assertNotSame($this->before['user'][0]['phone'], $this->rows()['user'][0]['phone']);
        } else {
            self::assertTrue(password_verify(self::PASSWORD, $user->getPassword()));
            self::assertCount(1, $this->rows()['user']);
            self::assertCount(1, $this->rows()['avatar']);
        }
    }

    public static function csrfCases(): iterable
    {
        foreach (['registration', 'profile'] as $journey) {
            yield "$journey missing" => [$journey, null];
            yield "$journey invalid" => [$journey, 'wrong-session-token'];
        }
    }

    #[DataProvider('csrfCases')]
    public function testCsrfRefusalHasNoEffect(string $journey, ?string $token): void
    {
        $this->open($journey);
        $payload = $this->payload($journey, '0698765432');
        if ($token !== null) $payload['_token'] = $token;
        $page = $this->submit($journey, $payload);
        self::assertSame(422, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString('CSRF', $page->text());
        $this->assertNoEffectAndReload();
    }

    public function testProfileRequiresAuthentication(): void
    {
        // Let the security exception listener translate AccessDenied into the login redirect.
        $this->client->catchExceptions(true);
        $this->before = $this->rows();
        foreach (['GET', 'POST'] as $method) {
            PhoneEffects::reset();
            $this->client->request($method, '/profile', ['update_user_form' => $this->payload('profile', '0698765432')]);
            self::assertSame(302, $this->client->getResponse()->getStatusCode());
            self::assertSame('/login', parse_url($this->client->getResponse()->headers->get('Location'), PHP_URL_PATH));
            $this->assertNoEffectAndReload();
        }
    }

    public static function registrationProtections(): iterable
    {
        yield 'consent missing' => ['agreeTerms', null, 'Vous devez accepter'];
        yield 'password missing' => ['plainPassword', null, 'Merci de renseigner un mot de passe'];
        yield 'password too short' => ['plainPassword', 'ab1!', 'au minimum 6'];
        yield 'password weak' => ['plainPassword', 'password', 'trop faible'];
        yield 'email already used' => ['email', 'existing@example.test', 'Il existe un compte avec cet email'];
    }

    #[DataProvider('registrationProtections')]
    public function testRegistrationProtectionsStillReject(string $field, ?string $value, string $message): void
    {
        if ($field === 'email') $this->fixtureUser('existing@example.test');
        $token = $this->open('registration');
        $payload = $this->payload('registration', '0698765432') + ['_token' => $token];
        if ($value === null) unset($payload[$field]);
        else $payload[$field] = $value;
        $page = $this->submit('registration', $payload);
        self::assertSame(422, $this->client->getResponse()->getStatusCode());
        $this->assertFieldError($page, 'registration', $field, $message);
        $this->assertNoEffectAndReload();
    }

    public function testPhoneConstructionAndMappingRemainUnchanged(): void
    {
        self::assertNull((new User())->getPhone());
        self::assertFalse($this->em->getClassMetadata(User::class)->getFieldMapping('phone')->nullable);
    }

    private function fixtureUser(string $email): User
    {
        $user = (new User())->setEmail($email)->setPassword('synthetic-unused-hash')
            ->setFirstname('Camille')->setLastname('Fictif')->setAdress('1 rue fictive')->setPostalCode('75001')->setCity('Paris')
            ->setPhone(PhoneNumberUtil::getInstance()->parse('0612345678', 'FR'));
        $avatar = new Avatar($user);
        $avatar->setImageName('existing-synthetic-avatar.png');
        $avatar->setUpdatedAt(new \DateTimeImmutable('2026-01-01T12:00:00Z'));
        $user->setAvatar($avatar);
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    private function open(string $journey): string
    {
        if ($journey === 'profile') {
            $user = $this->fixtureUser('client@example.test');
            $this->userId = $user->getId();
            $this->avatarId = $user->getAvatar()->getId();
            $this->client->loginUser($user);
        }
        $page = $this->client->request('GET', $this->uri($journey));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $token = $page->filter('input[name="'.$this->formName($journey).'[_token]"]')->attr('value');
        self::assertNotEmpty($token);
        $this->before = $this->rows();
        // Fixtures, loginUser and GET are outside the POST observation window.
        PhoneEffects::reset();
        return $token;
    }

    private function submit(string $journey, array $payload): Crawler
    {
        try {
            $page = $this->client->request('POST', $this->uri($journey), [$this->formName($journey) => $payload]);
            $this->record($journey, null);
            return $page;
        } catch (\Throwable $error) {
            $this->record($journey, $error);
            throw $error;
        }
    }

    private function record(string $journey, ?\Throwable $error): void
    {
        $path = getenv('PHONE_OBSERVATIONS');
        if (!$path) return;
        file_put_contents($path, json_encode(['test' => $this->nameWithDataSet(), 'journey' => $journey,
            'status' => $error ? null : $this->client->getResponse()->getStatusCode(),
            'exception' => $error ? get_class($error) : null, 'message' => $error?->getMessage(),
            'calls' => PhoneEffects::$calls, 'persisted_rows_unchanged' => $this->before === $this->rows()], JSON_THROW_ON_ERROR).PHP_EOL, FILE_APPEND);
    }

    private function assertNoEffectAndReload(): void
    {
        self::assertSame([], PhoneEffects::$calls, 'No persist/flush, avatar, login or send attempt is allowed.');
        $this->em->clear();
        self::assertSame($this->before, $this->rows(), 'Independent SQL re-read must match all User and Avatar columns.');
        if ($this->userId !== null) {
            $user = $this->em->find(User::class, $this->userId);
            self::assertSame('+33612345678', PhoneNumberUtil::getInstance()->format($user->getPhone(), PhoneNumberFormat::E164));
            self::assertSame($this->avatarId, $user->getAvatar()->getId());
            self::assertSame('existing-synthetic-avatar.png', $user->getAvatar()->getImageName());
        }
    }

    private function rows(): array
    {
        $db = $this->em->getConnection();
        return ['user' => $db->fetchAllAssociative('SELECT * FROM user ORDER BY id'),
            'avatar' => $db->fetchAllAssociative('SELECT * FROM avatar ORDER BY id')];
    }

    private function payload(string $journey, ?string $phone): array
    {
        $data = ['email' => 'client@example.test', 'firstname' => 'Camille', 'lastname' => 'Fictif',
            'adress' => '1 rue fictive', 'postalCode' => '75001', 'city' => 'Paris'];
        if ($phone !== null) $data['phone'] = $phone;
        if ($journey === 'registration') $data += ['agreeTerms' => '1', 'plainPassword' => self::PASSWORD];
        return $data;
    }

    private function field(Crawler $page, string $journey, string $field): Crawler
    {
        $input = $page->filter('#'.$this->formName($journey).'_'.$field);
        // Password-toggle and checkbox widgets insert wrappers between input and field errors.
        foreach ($input->ancestors() as $node) {
            $ancestor = new Crawler($node);
            if ($ancestor->filter('.invalid-feedback')->count() > 0) return $ancestor;
        }
        self::fail('No rendered field error found for '.$field);
    }

    private function assertFieldError(Crawler $page, string $journey, string $field, string $message): void
    {
        self::assertStringContainsString($message, $this->field($page, $journey, $field)->text());
        self::assertStringContainsString('is-invalid', $page->filter('#'.$this->formName($journey).'_'.$field)->attr('class'));
    }

    private function formName(string $journey): string { return $journey === 'registration' ? 'registration_form' : 'update_user_form'; }
    private function uri(string $journey): string { return $journey === 'registration' ? '/inscription' : '/profile'; }
}
