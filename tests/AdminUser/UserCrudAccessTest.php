<?php

namespace App\Tests\AdminUser;

use App\Controller\Admin\UserCrudController;
use App\Entity\Avatar;
use App\Entity\User;
use App\Tests\Prestations\CaptureAuthMailer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class UserCrudAccessTest extends TestCase
{
    private AdminUserKernel $kernel;
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $admin;
    private User $ordinary;
    private const PASSWORD = 'Synthetic-Only-8z!V4q#L2';

    protected function setUp(): void
    {
        $this->kernel = new AdminUserKernel('test', true);
        $this->kernel->boot();
        $container = $this->kernel->getContainer()->get('test.service_container');
        $this->em = $container->get('doctrine')->getManager();
        $params = $this->em->getConnection()->getParams();
        self::assertSame('pdo_sqlite', $params['driver']);
        self::assertTrue($params['memory'] ?? false);
        self::assertEmpty($params['path'] ?? null);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $this->ordinary = $this->user('ordinary@example.test', []);
        $this->admin = $this->user('admin@example.test', ['ROLE_ADMIN']);
        $this->em->flush();
        $this->client = new KernelBrowser($this->kernel);
        $this->client->disableReboot();
        CaptureAuthMailer::$codes = [];
        UserCrudWriteObserver::$calls = [];
    }

    public static function actions(): iterable
    {
        yield 'index' => [Action::INDEX];
        yield 'edit' => [Action::EDIT];
        yield 'new' => [Action::NEW];
    }

    #[DataProvider('actions')]
    public function testAnonymousIsRedirectedToLogin(string $action): void
    {
        $this->client->request('GET', $this->url($action));
        $this->assertRedirectTo('/login');
        self::assertSame(2, $this->em->getRepository(User::class)->count([]));
    }

    #[DataProvider('actions')]
    public function testOrdinaryUserIsForbidden(string $action): void
    {
        $this->client->loginUser($this->ordinary);
        $this->client->request('GET', $this->url($action));
        self::assertSame(403, $this->client->getResponse()->getStatusCode());
        self::assertSame(2, $this->em->getRepository(User::class)->count([]));
    }

    public function testRealTwoFactorChallengeBlocksAdminUntilCompleted(): void
    {
        $index = $this->url(Action::INDEX);
        $this->client->request('GET', $index);
        $this->assertRedirectTo('/login');
        $page = $this->client->followRedirect();
        $this->client->submit($page->filter('#login_form')->form([
            '_username' => $this->admin->getEmail(), '_password' => self::PASSWORD,
        ]));
        for ($i = 0; $i < 4 && $this->client->getResponse()->isRedirect(); ++$i) {
            self::assertContains(parse_url($this->client->getResponse()->headers->get('Location'), PHP_URL_PATH), [parse_url($index, PHP_URL_PATH), '/2fa']);
            $page = $this->client->followRedirect();
        }
        self::assertSame(1, $page->filter('input[name="_auth_code"]')->count());
        foreach ([Action::INDEX, Action::EDIT, Action::NEW] as $action) {
            $this->client->request('GET', $this->url($action));
            $this->assertRedirectTo('/2fa');
        }
        // Restore the requested destination before completing the real challenge.
        $this->client->request('GET', $index);
        $page = $this->client->followRedirect();
        self::assertArrayHasKey($this->admin->getEmail(), CaptureAuthMailer::$codes);
        $this->client->submit($page->filter('form')->form([
            '_auth_code' => CaptureAuthMailer::$codes[$this->admin->getEmail()],
        ]));
        self::assertSame(302, $this->client->getResponse()->getStatusCode());
        $this->client->request('GET', $index);
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString('ordinary@example.test', $this->client->getResponse()->getContent());
        $page = $this->client->request('GET', $this->url(Action::EDIT));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertSame(1, $page->filter('input[name="User[firstname]"]')->count());
        $this->assertNewForbidden();
    }

    public function testFullyAuthenticatedAdminCanEditWithRealFormAndCsrf(): void
    {
        $this->client->loginUser($this->admin);
        $page = $this->client->request('GET', $this->url(Action::EDIT));
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $form = $page->filter('form[name="User"]')->form([
            'User[firstname]' => 'mArIe', 'User[lastname]' => 'duPont',
        ]);
        self::assertSame([], UserCrudWriteObserver::$calls);
        $this->client->submit($form);
        self::assertSame(302, $this->client->getResponse()->getStatusCode());
        self::assertSame(['beforeUpdateEntity', 'persist', 'flush'], UserCrudWriteObserver::$calls);
        $id = $this->ordinary->getId();
        $this->em->clear();
        $user = $this->em->find(User::class, $id);
        self::assertSame('MArIe', $user->getFirstname());
        self::assertSame('DUPONT', $user->getLastname());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertSame(2, $this->em->getRepository(User::class)->count([]));
        $this->assertNewForbidden();
    }

    public static function incompleteNames(): iterable
    {
        foreach (['firstname', 'lastname'] as $field) {
            yield $field.' absent' => [[$field => null]];
            yield $field.' empty' => [[$field => '']];
            yield $field.' spaces' => [[$field => '   ']];
        }
        yield 'both empty' => [['firstname' => '', 'lastname' => '']];
    }

    #[DataProvider('incompleteNames')]
    public function testIncompleteNamesAreFieldErrorsWithoutHookOrWrite(array $values): void
    {
        $messages = ['firstname' => 'Merci de renseigner le prénom.', 'lastname' => 'Merci de renseigner le nom.'];
        $this->client->loginUser($this->admin);
        $url = $this->url(Action::EDIT);
        $page = $this->client->request('GET', $url);
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        $data = $page->filter('form[name="User"]')->form()->getPhpValues();
        self::assertNotEmpty($data['User']['_token']);
        foreach ($values as $field => $value) {
            if ($value === null) {
                unset($data['User'][$field]);
            } else {
                $data['User'][$field] = $value;
            }
        }
        $id = $this->ordinary->getId();
        $before = $this->em->getConnection()->fetchAssociative('SELECT * FROM user WHERE id = ?', [$id]);
        self::assertSame([], UserCrudWriteObserver::$calls);
        // Surface the original binding exception in the isolated counter-proof.
        $this->client->catchExceptions(false);
        $page = $this->client->request('POST', $url, $data);
        // EasyAdmin's installed CrudResponseListener returns 422 when the form has errors.
        self::assertSame(422, $this->client->getResponse()->getStatusCode());
        self::assertSame([], UserCrudWriteObserver::$calls);
        self::assertSame(0, $page->filter('.global-invalid-feedback')->count(), 'The valid CSRF token must not be rejected.');
        foreach ($messages as $field => $message) {
            $input = $page->filter('#User_'.$field);
            $errors = $input->ancestors()->filter('.form-widget')->first()->filter('.invalid-feedback');
            if (array_key_exists($field, $values)) {
                self::assertStringContainsString('is-invalid', $input->attr('class'));
                self::assertStringContainsString($message, implode(' ', $errors->each(static fn ($error): string => $error->text())));
                self::assertSame('', $input->attr('value') ?? '', 'Do not restore the old name silently.');
            } else {
                self::assertSame(0, $errors->count());
            }
        }
        $this->em->clear();
        $reloaded = $this->em->find(User::class, $id);
        self::assertInstanceOf(User::class, $reloaded);
        self::assertSame($before['firstname'], $reloaded->getFirstname());
        self::assertSame($before['lastname'], $reloaded->getLastname());
        self::assertSame($before, $this->em->getConnection()->fetchAssociative('SELECT * FROM user WHERE id = ?', [$id]));
    }

    public static function shortNames(): iterable
    {
        yield 'firstname' => ['firstname', 'Al'];
        yield 'lastname' => ['lastname', 'X'];
    }

    #[DataProvider('shortNames')]
    public function testExistingLengthConstraintsStillRejectShortNames(string $field, string $value): void
    {
        $this->client->loginUser($this->admin);
        $url = $this->url(Action::EDIT);
        $page = $this->client->request('GET', $url);
        $form = $page->filter('form[name="User"]')->form(['User['.$field.']' => $value]);
        $id = $this->ordinary->getId();
        $before = $this->em->getConnection()->fetchAssociative('SELECT * FROM user WHERE id = ?', [$id]);
        $page = $this->client->submit($form);
        self::assertSame(422, $this->client->getResponse()->getStatusCode());
        self::assertSame([], UserCrudWriteObserver::$calls);
        self::assertStringContainsString('is-invalid', $page->filter('#User_'.$field)->attr('class'));
        $errors = $page->filter('#User_'.$field)->ancestors()->filter('.form-widget')->first()->filter('.invalid-feedback')->text();
        self::assertStringContainsString($field === 'firstname' ? 'Votre prénom doit comporter' : 'Votre nom doit comporter', $errors);
        $this->em->clear();
        self::assertSame($before, $this->em->getConnection()->fetchAssociative('SELECT * FROM user WHERE id = ?', [$id]));
    }

    private function assertNewForbidden(): void
    {
        foreach (['GET', 'POST'] as $method) {
            $this->client->request($method, $this->url(Action::NEW));
            self::assertSame(403, $this->client->getResponse()->getStatusCode());
            self::assertSame(2, $this->em->getRepository(User::class)->count([]));
        }
    }

    private function url(string $action): string
    {
        $generator = $this->kernel->getContainer()->get('test.service_container')->get(AdminUrlGenerator::class);
        $generator->unsetAll()->setController(UserCrudController::class)->setAction($action);
        if ($action === Action::EDIT) $generator->setEntityId($this->ordinary->getId());
        return $generator->generateUrl();
    }

    private function user(string $email, array $roles): User
    {
        $user = (new User())->setEmail($email)->setRoles($roles)->setFirstname('Camille')->setLastname('Synthetic')
            ->setAdress('1 rue de test')->setPostalCode('75001')->setCity('Paris')
            ->setPhone(\libphonenumber\PhoneNumberUtil::getInstance()->parse('0612345678', 'FR'));
        $avatar = new Avatar($user);
        $avatar->setImageName('synthetic-avatar.png');
        $user->setAvatar($avatar);
        $hasher = $this->kernel->getContainer()->get('test.service_container')->get('security.user_password_hasher');
        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));
        $this->em->persist($user);
        return $user;
    }

    private function assertRedirectTo(string $path): void
    {
        self::assertSame(302, $this->client->getResponse()->getStatusCode());
        self::assertSame($path, parse_url($this->client->getResponse()->headers->get('Location'), PHP_URL_PATH));
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->close();
        $this->kernel->shutdown();
    }
}
