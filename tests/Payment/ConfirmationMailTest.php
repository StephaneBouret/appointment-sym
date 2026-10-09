<?php

namespace App\Tests\Payment;

use App\Event\AppointmentSuccessEvent;
use App\EventDispatcher\AppointmentEmailSuccessSubscriber;
use App\Service\SendMailService;
use App\Twig\AmountExtension;
use Symfony\Bridge\Twig\Mime\BodyRenderer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\EventListener\MessageListener;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class ConfirmationMailTest extends PaymentTestCase
{
    public function testRealSubscriberAndTemplateSendFrozenAmountOnlyOnce(): void
    {
        foreach ($this->events->getListeners(AppointmentSuccessEvent::NAME) as $listener) {
            $this->events->removeListener(AppointmentSuccessEvent::NAME, $listener);
        }
        $twig = new Environment(new FilesystemLoader(dirname(__DIR__, 2).'/templates'));
        $twig->addExtension(new AmountExtension());
        $twig->addExtension(new IntlExtension());
        $twig->addFunction(new TwigFunction('path', static fn (string $route): string => '/'.$route));
        $twig->addFunction(new TwigFunction('absolute_url', static fn (string $path): string => 'https://example.test'.$path));
        $mailEvents = new EventDispatcher();
        $mailEvents->addSubscriber(new MessageListener(renderer: new BodyRenderer($twig)));
        $mailEvents->addSubscriber($this->mailLog);
        $mail = new SendMailService(new Mailer(new NullTransport($mailEvents)), 'sender@example.test');
        $router = $this->createStub(UrlGeneratorInterface::class);
        $router->method('generate')->willReturn('https://example.test/rendez-vous/list');
        $this->events->addSubscriber(new AppointmentEmailSuccessSubscriber($mail, $router, 'sender@example.test'));
        $intent = $this->prepare();
        $this->appointment->getType()->setPrice(22000);
        $this->em->flush();
        $this->payments->confirmWebhook($intent);
        $this->payments->confirmWebhook($intent);
        self::assertSame(0, $this->mailCount());
        $tester = new \Symfony\Component\Console\Tester\CommandTester(
            new \App\Command\PaymentNotificationsCommand($this->em, $this->notifications)
        );
        self::assertSame(0, $tester->execute(['--retry' => true]));
        self::assertSame(0, $tester->execute(['--retry' => true]));
        self::assertSame('sent', $this->persisted()->getPayment()->notificationState);
        self::assertSame(1, $this->mailCount());
        $message = $this->mailLog->getEvents()->getMessages()[0];
        self::assertStringContainsString('150,00', $message->getHtmlBody());
        self::assertStringNotContainsString('220,00', $message->getHtmlBody());
        self::assertStringContainsString($this->appointment->getNumber(), $message->getHtmlBody());
        self::assertSame($this->appointment->getUser()->getEmail(), $message->getTo()[0]->getAddress());
    }
}
