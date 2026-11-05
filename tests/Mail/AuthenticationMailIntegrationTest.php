<?php

namespace App\Tests\Mail;

use App\Entity\User;
use App\Service\SendEmail2faService;
use App\Service\SendMailService;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class AuthenticationMailIntegrationTest extends KernelTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new MailKernel('test', true);
    }

    public static function identities(): iterable
    {
        yield 'anonymous context' => [false];
        yield 'different authenticated session identity' => [true];
    }

    #[DataProvider('identities')]
    public function testRealWiringAndPersonalizedTemplate(bool $otherIdentity): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $authMailer = $container->get('test.auth_mailer');
        self::assertSame(SendEmail2faService::class, $authMailer::class);
        self::assertSame(SendMailService::class, $container->get('test.mail_service')::class);
        self::assertSame($authMailer, $container->get('auth_mailer'));

        // Real Security and token storage in a request with a session; no Security mock.
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/synthetic-mail-context');
        $request->setSession($session);
        $stack = $container->get(RequestStack::class);
        $stack->push($request);
        $storage = $container->get(TokenStorageInterface::class);
        $other = (new User())->setEmail('other@example.test')->setFirstname('OtherIdentity')->setPassword('synthetic-hash');
        $other->setEmailAuthCode('999999');
        $token = $otherIdentity ? new UsernamePasswordToken($other, 'main', ['ROLE_USER']) : null;
        $session->set('_security_main', $token === null ? null : serialize($token));
        $storage->setToken($token);
        self::assertSame($otherIdentity ? $other : null, $container->get(Security::class)->getUser());
        $sessionBefore = $session->all();

        $log = new MessageLoggerListener();
        $container->get('event_dispatcher')->addSubscriber($log);
        $subject = (new User())->setEmail('subject@example.test')->setFirstname('CamilleSynthetic');
        $subject->setEmailAuthCode('654321');
        try {
            $authMailer->sendAuthCode($subject);
            $messages = $log->getEvents()->getMessages();
            self::assertCount(1, $messages);
            $message = $messages[0];
            self::assertCount(1, $message->getTo());
            self::assertSame('subject@example.test', $message->getTo()[0]->getAddress());
            self::assertSame('sender@example.test', $message->getFrom()[0]->getAddress());
            self::assertSame('Code de vérification : application Potentiel Consulting', $message->getFrom()[0]->getName());
            self::assertSame('Code de vérification', $message->getSubject());
            // Symfony clears template/context metadata after rendering.
            self::assertTrue($message->isRendered());
            self::assertStringContainsString('Bonjour CamilleSynthetic,', $message->getHtmlBody());
            self::assertStringContainsString('<strong>654321</strong>', $message->getHtmlBody());
            self::assertStringNotContainsString('OtherIdentity', $message->getHtmlBody());
            self::assertStringNotContainsString('999999', $message->getHtmlBody());
            self::assertSame($sessionBefore, $session->all());
            self::assertSame($token, $storage->getToken());
        } finally {
            $storage->setToken(null);
            $stack->pop();
            $session->invalidate();
        }
    }
}
