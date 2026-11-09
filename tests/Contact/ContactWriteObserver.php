<?php

namespace App\Tests\Contact;

use App\Entity\Contact;
use Doctrine\ORM\Decorator\EntityManagerDecorator;

// Observes the controller's write boundary while delegating to the real memory-backed ORM.
final class ContactWriteObserver extends EntityManagerDecorator
{
    /** @var list<string> */
    public array $calls = [];

    public function persist(object $object): void
    {
        if ($object instanceof Contact) {
            $this->calls[] = 'persist';
        }
        parent::persist($object);
    }

    public function flush(): void
    {
        parent::flush();
        $this->calls[] = 'flush';
    }
}
