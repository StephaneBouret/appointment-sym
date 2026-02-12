<?php

namespace App\Service;

class PurchaseNumberGenerator
{
    public function generate(): string
    {
        $datePart = (new \DateTime())->format('Ymd');
        $randomPart = random_int(10000, 99999);

        $orderNumber = $datePart . '-' . $randomPart;

        return $orderNumber;
    }
}