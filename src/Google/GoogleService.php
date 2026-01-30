<?php

namespace App\Google;

class GoogleService
{
    protected $googleKey;

    public function __construct(string $googleKey)
    {
        $this->googleKey = $googleKey;
    }

    public function getGoogleKey(): string
    {
        return $this->googleKey;
    }
}
