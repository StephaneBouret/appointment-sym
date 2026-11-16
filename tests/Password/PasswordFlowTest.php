<?php

namespace App\Tests\Password;

use App\Entity\Avatar;
use App\Entity\Company;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordFlowTest extends TestCase
{
    private const PATH = '/profile/editPassword';
    private const OLD_ERROR = 'Mauvaise valeur pour votre mot de passe actuel';
    private const BLANK_ERROR = 'Merci de renseigner votre nouveau mot de passe.';
    private const WEAK_ERROR = 'Le mot de passe est trop faible. Veuillez utiliser un mot de passe plus fort.';
    private const REPEAT_ERROR = 'Le mot de passe et la confirmation doivent être identiques';
    private const CSRF_ERROR = 'Le jeton CSRF est invalide. Veuillez renvoyer le formulaire.';
    private PasswordKernel $kernel;
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $user;
    private string $old;
    private string $new;

    protected function setUp(): void
    {
        require_once __DIR__.'/bootstrap.php';
        $this->kernel = new PasswordKernel('test', true);
        $this->kernel->boot();
        $container = $this->kernel->getContainer()->get('test.service_container');
        $container->set('http_client', new MockHttpClient(static function () {
            PasswordEffects::$calls[] = 'http';
            throw new \LogicException('External HTTP forbidden in password tests.');
        }));
        $this->em = $container->get('doctrine')->getManager();
        $params = $this->em->getConnection()->getParams();
        // Verify isolation BEFORE connecting, schema creation or fixture writes.
        self::assertSame('pdo_sqlite', $params['driver']);
        self::assertTrue($params['memory'] ?? false);
        self::assertEmpty($params['path'] ?? null);
        self::assertFalse($this->em->getConnection()->isConnected());
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $company = (new Company())->setName('Entreprise synthétique')->setSlug('synthetic')
            ->setAdress('1 rue fictive')->setPostalCode('75001')->setCity('Paris')->setEmail('company@example.test')
            ->setPhone(PhoneNumberUtil::getInstance()->parse('0612345678', 'FR'))->setType('TEST')
            ->setSiren('123456789')->setUrl('https://example.test')->setManager('Camille Fictif');
        $this->old = bin2hex(random_bytes(24));
        $this->new = bin2hex(random_bytes(24));
        $this->user = (new User())->setFirstname('Camille')->setLastname('FICTIF')->setEmail('client@example.test')
            ->setAdress('1 rue fictive')->setPostalCode('75001')->setCity('Paris')->setIsVerified(true)
            ->setPhone(PhoneNumberUtil::getInstance()->parse('0612345678', 'FR'));
        $this->user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($this->user, $this->old));
        $avatar = new Avatar($this->user);
        $avatar->setImageName('synthetic-avatar.png');
        $this->user->setAvatar($avatar);
        $this->em->persist($company);
        $this->em->persist($this->user);
        $this->em->flush();
        $this->client = new KernelBrowser($this->kernel);
        $this->client->disableReboot();
        $this->client->catchExceptions(true);
        PasswordEffects::reset();
    }

    public static function cases(): iterable
    {
        foreach (['wrong_old', 'old_missing', 'old_empty', 'all_missing', 'all_empty', 'new_missing', 'new_empty',
            'first_missing', 'second_missing', 'weak', 'mismatch', 'csrf_missing', 'csrf_invalid',
            'csrf_random_same_origin', 'csrf_no_origin', 'csrf_cross_origin', 'no_form', 'anonymous_get', 'anonymous_post', 'valid'] as $case) {
            yield $case => [$case];
        }
    }

    #[DataProvider('cases')]
    public function testPasswordChange(string $case): void
    {
        $anonymous = str_starts_with($case, 'anonymous');
        $payload = ['oldPassword' => $this->old, 'newPassword' => ['first' => $this->new, 'second' => $this->new]];
        if (!$anonymous) {
            $this->client->loginUser($this->user, 'main');
            $page = $this->client->request('GET', self::PATH);
            self::assertSame(200, $this->client->getResponse()->getStatusCode());
            $payload['_token'] = $page->filter('input[name="update_password_user_form[_token]"]')->attr('value');
        }
        switch ($case) {
            case 'wrong_old': $payload['oldPassword'] = bin2hex(random_bytes(24)); break;
            case 'old_missing': unset($payload['oldPassword']); break;
            case 'old_empty': $payload['oldPassword'] = ''; break;
            case 'all_missing': $payload = ['_token' => $payload['_token']]; break;
            case 'all_empty': $payload['oldPassword'] = ''; $payload['newPassword'] = ['first' => '', 'second' => '']; break;
            case 'new_missing': unset($payload['newPassword']); break;
            case 'new_empty': $payload['newPassword'] = ['first' => '', 'second' => '']; break;
            case 'first_missing': unset($payload['newPassword']['first']); break;
            case 'second_missing': unset($payload['newPassword']['second']); break;
            case 'weak': $payload['newPassword'] = ['first' => str_repeat('a', 4), 'second' => str_repeat('a', 4)]; break;
            case 'mismatch': $payload['newPassword']['second'] = bin2hex(random_bytes(24)); break;
            case 'csrf_missing': unset($payload['_token']); break;
            case 'csrf_invalid': $payload['_token'] = 'invalid'; break;
            case 'csrf_random_same_origin': case 'csrf_no_origin': case 'csrf_cross_origin': $payload['_token'] = bin2hex(random_bytes(24)); break;
        }
        $headers = ['HTTP_REFERER' => 'http://localhost'.self::PATH];
        if ($case === 'csrf_no_origin') $headers = ['HTTP_X_PASSWORD_TEST_NO_ORIGIN' => '1'];
        if ($case === 'csrf_cross_origin') $headers = ['HTTP_ORIGIN' => 'https://foreign.example.test', 'HTTP_SEC_FETCH_SITE' => 'cross-site'];
        $db = $this->em->getConnection();
        $before = $this->rows();
        $initialHash = $this->user->getPassword();
        $initialVersion = $this->user->getTrustedTokenVersion();
        $id = $this->user->getId();
        $trustedCookie = $this->trustedCookie();
        $changes = (int) $db->fetchOne('SELECT total_changes()');
        PasswordEffects::reset();
        $page = $this->client->request($case === 'anonymous_get' ? 'GET' : 'POST', self::PATH,
            $case === 'no_form' ? [] : ['update_password_user_form' => $payload], [], $headers);
        $response = $this->client->getResponse();
        $observed = ['case' => $case, 'status' => $response->getStatusCode(), 'location' => $response->headers->get('Location'),
            'errors' => PasswordEffects::$errors, 'effects' => PasswordEffects::$calls,
            'sql_changes' => (int) $db->fetchOne('SELECT total_changes()') - $changes];
        // Optional sanitized evidence; execution never depends on a campaign or var/ input file.
        if ($destination = getenv('PASSWORD_OBSERVATIONS')) file_put_contents($destination, json_encode($observed, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);

        $success = in_array($case, ['valid', 'csrf_random_same_origin'], true);
        if (!$success) {
            self::assertSame([], PasswordEffects::$calls);
            self::assertSame(0, $observed['sql_changes']);
            // Boolean assertions avoid exposing credential-bearing rows on failure.
            self::assertTrue($before === $this->rows(), 'Every business table must be unchanged.');
            $this->em->clear();
            $fresh = $this->em->find(User::class, $id);
            self::assertTrue($initialHash === $fresh->getPassword(), 'Stored hash must be unchanged.');
            self::assertSame($initialVersion, $fresh->getTrustedTokenVersion());
            self::assertTrue($this->isTrusted($fresh, $trustedCookie), 'Refusal must preserve the existing trusted device.');
        }
        if ($anonymous) {
            self::assertSame(302, $response->getStatusCode());
            self::assertSame('/login', parse_url($response->headers->get('Location'), PHP_URL_PATH));
            $this->client->followRedirect();
            self::assertSame(200, $this->client->getResponse()->getStatusCode());
            return;
        }
        if ($success) {
            self::assertSame(302, $response->getStatusCode());
            self::assertSame('/', $response->headers->get('Location'));
            self::assertSame(['flush'], PasswordEffects::$calls);
            self::assertSame(1, $observed['sql_changes']);
            $this->em->clear();
            $fresh = $this->em->find(User::class, $id);
            $container = $this->kernel->getContainer()->get('test.service_container');
            $hasher = $container->get(UserPasswordHasherInterface::class);
            self::assertTrue($hasher->isPasswordValid($fresh, $this->new));
            self::assertFalse($hasher->isPasswordValid($fresh, $this->old));
            self::assertSame($initialVersion + 1, $fresh->getTrustedTokenVersion());
            // Decode the actual signed cookie generated BEFORE the password change.
            self::assertFalse($this->isTrusted($fresh, $trustedCookie));
            $page = $this->client->followRedirect();
            self::assertSame(200, $this->client->getResponse()->getStatusCode());
            self::assertStringContainsString('Votre mot de passe a bien été mis à jour', $page->filter('.alert')->text());
            $this->em->clear();
            $this->client->request('GET', self::PATH);
            self::assertSame(200, $this->client->getResponse()->getStatusCode(), 'Existing authenticated session remains usable.');
            return;
        }
        self::assertSame($case === 'no_form' ? 200 : 422, $response->getStatusCode());
        self::assertFalse($response->headers->has('Location'));
        self::assertSame('false', $page->filter('form[name="update_password_user_form"]')->attr('data-turbo'));
        self::assertCount(0, $page->filter('.alert-warning, .alert-success'));
        $expected = match ($case) {
            'wrong_old', 'old_missing', 'old_empty' => [['oldPassword', self::OLD_ERROR]],
            'all_missing', 'all_empty' => [['oldPassword', self::OLD_ERROR], ['newPassword.first', self::BLANK_ERROR]],
            'new_missing', 'new_empty' => [['newPassword.first', self::BLANK_ERROR]],
            'weak' => [['newPassword.first', self::WEAK_ERROR]],
            'first_missing', 'second_missing', 'mismatch' => [['newPassword.first', self::REPEAT_ERROR]],
            'csrf_missing', 'csrf_invalid', 'csrf_no_origin', 'csrf_cross_origin' => [['', self::CSRF_ERROR]],
            'no_form' => [],
        };
        self::assertSame(array_map(static fn ($entry) => ['field' => $entry[0], 'message' => $entry[1]], $expected), PasswordEffects::$errors);
        foreach ($expected as [$field, $message]) {
            self::assertStringContainsString($message, $page->text());
            if ($field !== '') {
                $input = $page->filter('#update_password_user_form_'.str_replace('.', '_', $field));
                self::assertStringContainsString($message, $input->ancestors()->filter('.w-100')->first()->text());
            } else {
                self::assertStringContainsString($message, $page->filter('form > .alert-danger')->text());
            }
        }
        foreach ($page->filter('input[type="password"]') as $input) self::assertSame('', $input->getAttribute('value'));
        foreach ([$this->old, $this->new, $payload['oldPassword'] ?? '', $payload['newPassword']['first'] ?? '', $payload['newPassword']['second'] ?? ''] as $plain) {
            if ($plain !== '') self::assertFalse(str_contains($response->getContent(), $plain), 'Submitted password must not appear in HTML.');
        }
        self::assertFalse(str_contains($response->getContent(), $initialHash), 'Stored hash must not appear in HTML.');
    }

    private function trustedCookie(): string
    {
        $container = $this->kernel->getContainer()->get('test.service_container');
        $stack = $container->get('request_stack');
        $storage = $container->get('test.password_trusted_storage');
        $storage->reset();
        $stack->push(Request::create('/'));
        try {
            $trusted = $container->get('test.password_trusted_manager');
            $trusted->addTrustedDevice($this->user, 'main');
            self::assertTrue($trusted->isTrustedDevice($this->user, 'main'));
            $cookie = $storage->getCookieValue();
            self::assertTrue(is_string($cookie) && $cookie !== '', 'Synthetic trusted cookie must exist.');
            return $cookie;
        } finally { $storage->reset(); $stack->pop(); }
    }

    private function isTrusted(User $user, string $cookie): bool
    {
        $container = $this->kernel->getContainer()->get('test.service_container');
        $stack = $container->get('request_stack');
        $storage = $container->get('test.password_trusted_storage');
        $storage->reset();
        $stack->push(Request::create('/', 'GET', [], ['trusted_device' => $cookie]));
        try { return $container->get('test.password_trusted_manager')->isTrustedDevice($user, 'main'); }
        finally { $storage->reset(); $stack->pop(); }
    }

    private function rows(): array
    {
        $db = $this->em->getConnection();
        $rows = [];
        foreach ($db->createSchemaManager()->listTableNames() as $table) $rows[$table] = $db->fetchAllAssociative('SELECT * FROM "'.$table.'"');
        return $rows;
    }

    protected function tearDown(): void
    {
        if (isset($this->em)) $this->em->getConnection()->close();
        if (isset($this->kernel)) $this->kernel->shutdown();
        PasswordEffects::reset();
    }
}
