<?php

namespace App\Tests\AdminUser;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;

// Delegates every call to the real ORM manager supplied by EasyAdmin.
final class UserCrudWriteObserver extends EntityManagerDecorator
{
    /** @var list<string> */
    public static array $calls = [];

    public function beforeUpdateEntity(BeforeEntityUpdatedEvent $event): void
    {
        // EasyAdmin dispatches this event directly before calling updateEntity().
        self::$calls[] = 'beforeUpdateEntity';
    }

    public function persist(object $object): void
    {
        self::$calls[] = 'persist';
        parent::persist($object);
    }

    public function flush(): void
    {
        self::$calls[] = 'flush';
        parent::flush();
    }
}
