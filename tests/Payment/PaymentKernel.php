<?php

namespace App\Tests\Payment;

/** Real application configuration, isolated cache and hard-coded test infrastructure. */
final class PaymentKernel extends \App\Kernel
{
    public function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/payment-tests/cache';
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/payment-tests/log';
    }
}
