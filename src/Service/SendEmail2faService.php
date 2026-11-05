<?php

namespace App\Service;

use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

final class SendEmail2faService implements AuthCodeMailerInterface
{
    public function __construct(protected SendMailService $email) {}

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $authCode = $user->getEmailAuthCode();

        $this->email->sendMail(
            null,
            'Code de vérification : application Potentiel Consulting',
            $user->getEmailAuthRecipient(),
            'Code de vérification',
            'authentication',
            [
                'user' => $user,
                'authCode' => $authCode
            ]
        );
    }
}
