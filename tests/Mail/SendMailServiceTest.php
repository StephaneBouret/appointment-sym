<?php

namespace App\Tests\Mail;

use App\Entity\User;
use App\Service\SendMailService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;

final class SendMailServiceTest extends TestCase
{
    public static function senders(): iterable
    {
        yield 'explicit sender, positional call' => ['explicit@example.test', 'explicit@example.test', false];
        yield 'default sender, positional call' => [null, 'default@example.test', false];
        yield 'explicit sender, named call' => ['explicit@example.test', 'explicit@example.test', true];
        yield 'default sender, named call' => [null, 'default@example.test', true];
    }

    #[DataProvider('senders')]
    public function testMessageAndSingleDelivery(?string $from, string $expectedFrom, bool $named): void
    {
        $context = ['user' => new User(), 'date' => new \DateTimeImmutable('2030-01-01'), 'amount' => 1200, 'optional' => null];
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->willReturnCallback(
            static function (TemplatedEmail $message) use ($expectedFrom, $context): void {
                self::assertCount(1, $message->getFrom());
                self::assertSame($expectedFrom, $message->getFrom()[0]->getAddress());
                self::assertSame('Synthetic sender', $message->getFrom()[0]->getName());
                self::assertCount(1, $message->getTo());
                self::assertSame('recipient@example.test', $message->getTo()[0]->getAddress());
                self::assertSame('Synthetic subject', $message->getSubject());
                self::assertSame('emails/authentication.html.twig', $message->getHtmlTemplate());
                self::assertSame($context, $message->getContext());
            }
        );
        $service = new SendMailService($mailer, 'default@example.test');
        if ($named) {
            $service->sendMail(context: $context, template: 'authentication', subject: 'Synthetic subject',
                to: 'recipient@example.test', name: 'Synthetic sender', from: $from);
        } else {
            $service->sendMail($from, 'Synthetic sender', 'recipient@example.test', 'Synthetic subject', 'authentication', $context);
        }
    }

    public function testTransportFailureIsPropagatedWithoutRetryOrSuccess(): void
    {
        $failure = new TransportException('Synthetic transport failure');
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send')->willThrowException($failure);
        $service = new SendMailService($mailer, 'default@example.test');
        $this->expectExceptionObject($failure);
        $service->sendMail(null, 'Synthetic sender', 'recipient@example.test', 'Subject', 'authentication', []);
    }
}
