<?php

namespace App\Tests\Phone;

use App\Entity\Avatar;
use App\Entity\User;
use App\Service\AvatarService;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

final class PhoneEffects
{
    /** @var list<string> */
    public static array $calls = [];

    public static function reset(): void { self::$calls = []; }
}

// Record before delegation, including a failing write attempt.
final class PhoneWriteObserver extends EntityManagerDecorator
{
    public function persist(object $object): void
    {
        PhoneEffects::$calls[] = 'persist:'.get_class($object);
        parent::persist($object);
    }

    public function flush(): void
    {
        PhoneEffects::$calls[] = 'flush';
        parent::flush();
    }
}

final class PhoneAvatarService extends AvatarService
{
    public function createAndAssignAvatar(User $user): Avatar
    {
        PhoneEffects::$calls[] = 'avatar.create';
        $avatar = new Avatar($user);
        $avatar->setImageName('synthetic-phone-avatar.png');
        return $avatar;
    }
}

// Observable terminal doubles: no transport or external service can be invoked.
final class PhoneMailer implements MailerInterface
{
    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        PhoneEffects::$calls[] = 'mail.send';
    }
}

final class PhoneAuthMailer implements AuthCodeMailerInterface
{
    public function sendAuthCode(TwoFactorInterface $user): void
    {
        PhoneEffects::$calls[] = 'auth_mail.send';
    }
}
