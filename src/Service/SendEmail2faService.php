<?php

namespace App\Service;

use App\Service\SendMailService;
use Symfony\Bundle\SecurityBundle\Security;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;

final class SendEmail2faService implements AuthCodeMailerInterface
{
    public function __construct(protected SendMailService $email, protected Security $security) {}

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $authCode = $user->getEmailAuthCode();

        // Send Email
        /** @var User */
        $user = $this->security->getUser();
        $this->email->sendMail(
            null,
            'Code de vérification : application Potentiel Consulting',
            $user->getEmail(),
            'Code de vérification',
            'authentication',
            [
                'user' => $user,
                'authCode' => $authCode
            ]
        );
    }
}
