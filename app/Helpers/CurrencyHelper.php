<?php

namespace App\Helpers;

class CurrencyHelper
{
    public const CURRENCIES = [
        'GBP' => '£',
        'USD' => '$',
        'EUR' => '€',
        'JPY' => '¥',
        'CAD' => 'C$',
        'AUD' => 'A$',
    ];

    public static function getSymbol(string $currency = 'GBP'): string
    {
        return self::CURRENCIES[$currency] ?? '£';
    }

    public static function format(float $amount, string $currency = 'GBP', int $decimals = 2): string
    {
        $symbol = self::getSymbol($currency);
        return $symbol . number_format($amount, $decimals);
    }

    public static function getAllCurrencies(): array
    {
        return self::CURRENCIES;
    }
}
