<?php

namespace App\Tests\Password;

use App\Form\UpdatePasswordUserFormType;
use App\Google\GoogleService;
use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

final class PasswordEffects
{
    /** @var list<string> */
    public static array $calls = [];
    /** @var list<array{field: string, message: string}> */
    public static array $errors = [];

    public static function reset(): void { self::$calls = self::$errors = []; }
}

final class PasswordWriteObserver extends EntityManagerDecorator
{
    public function persist(object $object): void
    {
        PasswordEffects::$calls[] = 'persist';
        parent::persist($object);
    }

    public function flush(): void
    {
        PasswordEffects::$calls[] = 'flush';
        parent::flush();
    }
}

// Observe error origins after validation, without changing form options or constraints.
final class PasswordFormObserver extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable { yield UpdatePasswordUserFormType::class; }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            foreach ($event->getForm()->getErrors(true) as $error) {
                $path = [];
                for ($origin = $error->getOrigin(); $origin && $origin->getParent(); $origin = $origin->getParent()) {
                    array_unshift($path, $origin->getName());
                }
                PasswordEffects::$errors[] = ['field' => implode('.', $path), 'message' => $error->getMessage()];
            }
        }, -2048);
    }
}

// BrowserKit adds a Referer automatically; this test-only marker removes origin information completely.
final class PasswordOriginObserver implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array { return ['kernel.request' => ['onRequest', 2048]]; }

    public function onRequest(RequestEvent $event): void
    {
        $headers = $event->getRequest()->headers;
        if ($headers->has('X-Password-Test-No-Origin')) {
            foreach (['Origin', 'Referer', 'Sec-Fetch-Site', 'X-Password-Test-No-Origin'] as $name) $headers->remove($name);
        }
    }
}

final class PasswordMailer implements MailerInterface
{
    public function send(RawMessage $message, ?Envelope $envelope = null): void { PasswordEffects::$calls[] = 'mail'; }
}

final class PasswordAuthMailer implements AuthCodeMailerInterface
{
    public function sendAuthCode(TwoFactorInterface $user): void { PasswordEffects::$calls[] = 'auth_mail'; }
}

final class PasswordGoogleService extends GoogleService
{
    public function getPlaceReviewsSummary(): array
    {
        return ['rating' => null, 'reviewCount' => null, 'googleMapsUri' => null, 'displayName' => null];
    }
}
