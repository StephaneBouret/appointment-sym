<?php

namespace App\Event;

use App\Entity\Contact;
use Symfony\Contracts\EventDispatcher\Event;

class ContactSuccessEvent extends Event
{
    public function __construct(protected Contact $contact)
    {}

    public function getContact(): Contact
    {
        return $this->contact;
    }
}
