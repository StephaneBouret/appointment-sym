<?php

namespace App\EventDispatcher;

use App\Event\ContactSuccessEvent;
use App\Service\SendMailService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContactEmailSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(protected SendMailService $sendMailService, protected string $defaultFrom)
    {}

    public static function getSubscribedEvents(): array
    {
        return [
            'contact.success' => 'sendSuccessEmail'
        ];
    }

    public function sendSuccessEmail(ContactSuccessEvent $contactSuccessEvent)
    {
        $contact = $contactSuccessEvent->getContact();
        $this->sendMailService->sendMail(
            null,
            'Demande de contact',
            $this->defaultFrom,
            'Demande de contact',
            'contact',
            ['contact' => $contact]
        );
    }
}
