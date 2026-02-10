<?php

namespace App\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;

class AmountExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('amount', [$this, 'formatPrice']),
        ];
    }

    public function formatPrice($value, string $symbol = '€', string $decsep = ',', string $thousandsep = ' '): string
    {
        $finalValue = $value / 100;
        $finalValue = number_format($finalValue, 2, $decsep, $thousandsep);

        return $finalValue . ' ' . $symbol;
    }
}