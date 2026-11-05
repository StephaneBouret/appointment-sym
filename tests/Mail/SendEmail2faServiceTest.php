<?php

namespace App\Tests\Mail;

use App\Entity\User;
use App\Service\SendEmail2faService;
use App\Service\SendMailService;
use PHPUnit\Framework\TestCase;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

final class SendEmail2faServiceTest extends TestCase
{
    public function testRecipientCodeAndContextUseTheProvidedInterfaceWithoutSecurity(): void
    {
        // This interface has no getEmail(): the recipient contract must be respected.
        $user = $this->createMock(TwoFactorInterface::class);
        $user->expects(self::once())->method('getEmailAuthRecipient')->willReturn('subject@example.test');
        $user->expects(self::once())->method('getEmailAuthCode')->willReturn('654321');
        $mail = $this->createMock(SendMailService::class);
        $mail->expects(self::once())->method('sendMail')->with(
            null, 'Code de vérification : application Potentiel Consulting', 'subject@example.test',
            'Code de vérification', 'authentication', self::callback(static function (array $context) use ($user): bool {
                self::assertSame(['user' => $user, 'authCode' => '654321'], $context);
                return true;
            })
        );
        (new SendEmail2faService($mail))->sendAuthCode($user);
    }

    public function testCodeRetrievalExceptionPreventsAnySend(): void
    {
        $user = $this->createMock(TwoFactorInterface::class);
        $failure = new \LogicException('Synthetic code retrieval failure');
        $user->expects(self::once())->method('getEmailAuthCode')->willThrowException($failure);
        $user->expects(self::never())->method('getEmailAuthRecipient');
        $mail = $this->createMock(SendMailService::class);
        $mail->expects(self::never())->method('sendMail');
        $this->expectExceptionObject($failure);
        (new SendEmail2faService($mail))->sendAuthCode($user);
    }

    public function testUninitializedCodeOnRealUserAlsoPreventsAnySend(): void
    {
        $user = (new User())->setEmail('subject@example.test')->setFirstname('Synthetic');
        $mail = $this->createMock(SendMailService::class);
        $mail->expects(self::never())->method('sendMail');
        // Existing entity behavior, outside this lot: authCode has no default value.
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('authCode must not be accessed before initialization');
        (new SendEmail2faService($mail))->sendAuthCode($user);
    }
}
