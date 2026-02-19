<?php

namespace App\EventSubscriber;

use App\Entity\Setting;
use App\Service\SettingService;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class InvalidateMaintenanceCacheSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly SettingService $settings) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityUpdatedEvent::class => 'onBeforeEntityUpdated'
        ];
    }

    public function onBeforeEntityUpdated(BeforeEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof Setting) {
            return;
        }

        if ($entity->getSettingKey() === Setting::KEY_MAINTENANCE) {
            $this->settings->invalidateMaintenance();
        }
    }
}
