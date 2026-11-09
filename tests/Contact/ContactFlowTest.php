<?php

namespace App\Tests\Contact;

use App\Entity\Avatar;
use App\Entity\Contact;
use App\Entity\User;
use App\Event\ContactSuccessEvent;
use App\Form\ContactFormType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;

final class ContactFlowTest extends TestCase
{
    private const REQUIRED_PHONE = 'Merci de renseigner votre numéro de téléphone.';
    private ContactKernel $kernel;
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private ContactWriteObserver $writes;
    private MessageLoggerListener $mail;
    /** @var list<Contact> Only contact.success, never Symfony's internal events. */
    private array $successes = [];

    protected function setUp(): void
    {
        require_once __DIR__.'/bootstrap.php';
        $this->kernel = new ContactKernel('test', true);
        $this->kernel->boot();
        $container = $this->kernel->getContainer()->get('test.service_container');
        $this->em = $container->get('doctrine')->getManager();
        $params = $this->em->getConnection()->getParams();
        self::assertSame('pdo_sqlite', $params['driver']);
        self::assertTrue($params['memory'] ?? false);
        self::assertEmpty($params['path'] ?? null);
        // Check destination before any schema or fixture write.
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $this->writes = $container->get(ContactWriteObserver::class);
        $this->mail = new MessageLoggerListener();
        $dispatcher = $container->get('event_dispatcher');
        $dispatcher->addSubscriber($this->mail);
        $dispatcher->addListener('contact.success', function (ContactSuccessEvent $event): void {
            $contact = $event->getContact();
            self::assertNotNull($contact->getId(), 'Business success must follow persistence.');
            self::assertSame(1, $this->contactCount(), 'Row must already be visible in SQL at dispatch.');
            self::assertSame(['persist', 'flush'], $this->writes->calls);
            $this->writes->calls[] = 'contact.success';
            $this->successes[] = $contact;
        }, 100);
        $this->client = new KernelBrowser($this->kernel);
        $this->client->disableReboot();
        $this->client->catchExceptions(false);
    }

    protected function tearDown(): void
    {
        if (isset($this->em)) {
            $this->em->getConnection()->close();
        }
        if (isset($this->kernel)) {
            $this->kernel->shutdown();
        }
    }

    public static function invalidPhones(): iterable
    {
        yield 'missing' => [null, true];
        yield 'empty' => ['', true];
        yield 'whitespace' => ['   ', true];
        yield 'unparseable' => ['not-a-phone-number', false];
        yield 'parseable but invalid' => ['0123', false];
    }

    #[DataProvider('invalidPhones')]
    public function testRealFormRejectsInvalidPhone(?string $phone, bool $missing): void
    {
        $contact = new Contact();
        $form = $this->kernel->getContainer()->get('test.service_container')->get('form.factory')
            ->create(ContactFormType::class, $contact, ['csrf_protection' => false]);
        $form->submit($this->payload($phone));
        self::assertTrue($form->isSubmitted());
        self::assertFalse($form->isValid());
        $errors = iterator_to_array($form->get('phone')->getErrors(true));
        self::assertNotEmpty($errors, 'The error belongs to the phone field.');
        if ($missing) {
            self::assertNull($contact->getPhone());
            self::assertSame(self::REQUIRED_PHONE, $errors[0]->getMessage());
        }
        $this->assertNoContactEffect();
    }

    public function testRealFormTransformsValidPhoneAndPreservesConstructionAndSchema(): void
    {
        $contact = new Contact();
        self::assertNull($contact->getPhone());
        self::assertFalse($this->em->getClassMetadata(Contact::class)->getFieldMapping('phone')->nullable);
        $form = $this->kernel->getContainer()->get('test.service_container')->get('form.factory')
            ->create(ContactFormType::class, $contact, ['csrf_protection' => false]);
        $form->submit($this->payload('06 12 34 56 78'));
        self::assertTrue($form->isValid(), (string) $form->getErrors(true));
        self::assertInstanceOf(PhoneNumber::class, $contact->getPhone());
        self::assertSame('+33612345678', PhoneNumberUtil::getInstance()->format($contact->getPhone(), PhoneNumberFormat::E164));
        $this->assertNoContactEffect();
    }

    #[DataProvider('invalidPhones')]
    public function testAnonymousPostWithValidCsrfRejectsInvalidPhone(?string $phone, bool $missing): void
    {
        $page = $this->openContact();
        $payload = $this->payload($phone);
        $payload['_token'] = $this->csrf($page);
        $page = $this->client->request('POST', '/contact', ['contact_form' => $payload]);
        self::assertSame(422, $this->client->getResponse()->getStatusCode());
        $field = $page->filter('#contact_form_phone')->ancestors()->first();
        self::assertNotSame('', trim($field->text()), 'Phone validation error must be rendered beside the field.');
        if ($missing) {
            self::assertStringContainsString(self::REQUIRED_PHONE, $field->text());
        }
        $this->assertNoContactEffect();
    }

    public static function actors(): iterable
    {
        yield 'anonymous' => [false];
        yield 'authenticated with prefilled details' => [true];
    }

    #[DataProvider('actors')]
    public function testValidHttpSubmissionPersistsThenDispatchesAndNotifiesOnce(bool $authenticated): void
    {
        if ($authenticated) {
            $this->loginSyntheticUser();
        }
        $page = $this->openContact();
        $form = $page->selectButton('Nous contacter')->form();
        if ($authenticated) {
            self::assertSame('camille', $form['contact_form[firstname]']->getValue());
            self::assertSame('fictif', $form['contact_form[lastname]']->getValue());
            self::assertSame('client@example.test', $form['contact_form[email]']->getValue());
            self::assertSame('06 12 34 56 78', $form['contact_form[phone]']->getValue());
            $form['contact_form[content]'] = 'Une demande synthétique suffisamment longue.';
        } else {
            foreach ($this->payload('06 12 34 56 78') as $key => $value) {
                $form['contact_form['.$key.']'] = $value;
            }
        }
        $this->assertNoContactEffect();
        $this->client->submit($form);
        self::assertSame(302, $this->client->getResponse()->getStatusCode());
        self::assertContains($this->client->getResponse()->headers->get('Location'), ['/', 'http://localhost/']);
        self::assertSame(1, $this->contactCount());
        self::assertCount(1, $this->successes);
        self::assertSame(['persist', 'flush', 'contact.success'], $this->writes->calls);
        $id = $this->successes[0]->getId();
        $this->em->clear();
        $contact = $this->em->find(Contact::class, $id);
        self::assertSame('Camille', $contact->getFirstname());
        self::assertSame('FICTIF', $contact->getLastname());
        self::assertSame($authenticated ? 'client@example.test' : 'contact@example.test', $contact->getEmail());
        self::assertInstanceOf(PhoneNumber::class, $contact->getPhone());
        self::assertSame('+33612345678', PhoneNumberUtil::getInstance()->format($contact->getPhone(), PhoneNumberFormat::E164));
        $messages = $this->mail->getEvents()->getMessages();
        self::assertCount(1, $messages, 'The real contact notification uses only the null test transport.');
        $message = $messages[0];
        self::assertTrue($message->isRendered());
        self::assertSame('Demande de contact', $message->getSubject());
        self::assertCount(1, $message->getTo());
        self::assertSame('sender@example.test', $message->getTo()[0]->getAddress());
        self::assertStringContainsString('Camille FICTIF', $message->getHtmlBody());
        self::assertStringContainsString('06 12 34 56 78', $message->getHtmlBody());
    }

    public function testClearingPrefilledPhoneIsRejectedWithoutChangingUser(): void
    {
        $user = $this->loginSyntheticUser();
        $page = $this->openContact();
        $form = $page->selectButton('Nous contacter')->form();
        self::assertSame('06 12 34 56 78', $form['contact_form[phone]']->getValue());
        $form['contact_form[phone]'] = '';
        $form['contact_form[content]'] = 'Une demande synthétique suffisamment longue.';
        $page = $this->client->submit($form);
        self::assertSame(422, $this->client->getResponse()->getStatusCode());
        self::assertStringContainsString(self::REQUIRED_PHONE, $page->filter('#contact_form_phone')->ancestors()->first()->text());
        $this->assertNoContactEffect();
        $this->em->clear();
        $storedUser = $this->em->find(User::class, $user->getId());
        self::assertSame('+33612345678', PhoneNumberUtil::getInstance()->format($storedUser->getPhone(), PhoneNumberFormat::E164));
    }

    public static function invalidTokens(): iterable
    {
        yield 'missing token' => [null];
        yield 'invalid token' => ['not-the-session-token'];
    }

    #[DataProvider('invalidTokens')]
    public function testCsrfRefusalNeverWritesOrDispatches(?string $token): void
    {
        $this->openContact();
        $payload = $this->payload('06 12 34 56 78');
        if ($token !== null) {
            $payload['_token'] = $token;
        }
        $this->client->request('POST', '/contact', ['contact_form' => $payload]);
        self::assertSame(422, $this->client->getResponse()->getStatusCode());
        $this->assertNoContactEffect();
    }

    private function openContact(): Crawler
    {
        $page = $this->client->request('GET', '/contact');
        self::assertSame(200, $this->client->getResponse()->getStatusCode());
        self::assertCount(1, $page->filter('input[name="contact_form[_token]"]'));
        return $page;
    }

    private function csrf(Crawler $page): string
    {
        $token = $page->filter('input[name="contact_form[_token]"]')->attr('value');
        self::assertNotEmpty($token);
        return $token;
    }

    /** @return array<string, string> */
    private function payload(?string $phone): array
    {
        $payload = ['firstname' => 'camille', 'lastname' => 'fictif', 'email' => 'contact@example.test',
            'content' => 'Une demande synthétique suffisamment longue.'];
        if ($phone !== null) {
            $payload['phone'] = $phone;
        }
        return $payload;
    }

    private function contactCount(): int
    {
        return (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM contact');
    }

    private function assertNoContactEffect(): void
    {
        self::assertSame(0, $this->contactCount());
        self::assertSame([], $this->writes->calls, 'Refusal must not even reach persist/flush.');
        self::assertSame([], $this->successes);
        self::assertCount(0, $this->mail->getEvents()->getMessages());
    }

    private function loginSyntheticUser(): User
    {
        $user = (new User())->setEmail('client@example.test')->setPassword('unused-synthetic-hash')
            ->setFirstname('camille')->setLastname('fictif')->setAdress('1 rue fictive')
            ->setPostalCode('75001')->setCity('Paris')
            ->setPhone(PhoneNumberUtil::getInstance()->parse('0612345678', 'FR'));
        $avatar = new Avatar($user);
        $avatar->setImageName('synthetic-avatar.png');
        $user->setAvatar($avatar);
        $this->em->persist($user);
        $this->em->flush();
        $this->client->loginUser($user);
        return $user;
    }
}
