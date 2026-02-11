<?php

namespace App\Doctrine\Listener;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class NormalizeAppointmentTimezoneListener
{
    private \DateTimeZone $utc;

    public function __construct()
    {
        $this->utc = new \DateTimeZone('UTC');
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $e = $args->getObject();
        if (!$e instanceof Appointment) return;

        if ($e->getStartAt() && $e->getStartAt()->getTimezone()->getName() !== 'UTC') {
            $e->setStartAt($e->getStartAt()->setTimezone($this->utc));
        }
        if ($e->getEndAt() && $e->getEndAt()->getTimezone()->getName() !== 'UTC') {
            $e->setEndAt($e->getEndAt()->setTimezone($this->utc));
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $e = $args->getObject();
        if (!$e instanceof Appointment) return;

        if ($e->getStartAt() && $e->getStartAt()->getTimezone()->getName() !== 'UTC') {
            $new = $e->getStartAt()->setTimezone($this->utc);
            $e->setStartAt($new);
            if ($args->hasChangedField('startAt')) {
                $args->setNewValue('startAt', $new);
            }
        }
        if ($e->getEndAt() && $e->getEndAt()->getTimezone()->getName() !== 'UTC') {
            $new = $e->getEndAt()->setTimezone($this->utc);
            $e->setEndAt($new);
            if ($args->hasChangedField('endAt')) {
                $args->setNewValue('endAt', $new);
            }
        }
    }
}
