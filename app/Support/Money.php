<?php

namespace App\Support;

/**
 * Formats minor-unit money amounts consistently across the UI.
 *
 * EventFlow stores every amount in the currency's minor unit (kobo for NGN,
 * cents for USD) so arithmetic stays integral, which means every display value
 * must be converted deliberately rather than by accident.
 */
final class Money
{
    /**
     * @var array<string, string>
     */
    private const SYMBOLS = [
        'NGN' => '₦',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'GHS' => '₵',
        'ZAR' => 'R',
        'KES' => 'KSh',
    ];

    public static function symbol(string $currency): string
    {
        return self::SYMBOLS[strtoupper($currency)] ?? strtoupper($currency).' ';
    }

    /**
     * Compact money, e.g. ₦12,500 or ₦1.2M, for metric tiles and chart axes.
     */
    public static function compact(int $minor, string $currency = 'NGN'): string
    {
        $major = $minor / 100;
        $symbol = self::symbol($currency);

        return match (true) {
            $major >= 1_000_000 => $symbol.rtrim(rtrim(number_format($major / 1_000_000, 1), '0'), '.').'M',
            $major >= 10_000 => $symbol.rtrim(rtrim(number_format($major / 1_000, 1), '0'), '.').'k',
            $major >= 1_000 => $symbol.number_format($major, 0),
            default => $symbol.number_format($major, $major === floor($major) ? 0 : 2),
        };
    }

    /**
     * Full precision, e.g. ₦4,500.00 or ₦4,500 for whole amounts.
     */
    public static function format(int $minor, string $currency = 'NGN'): string
    {
        $major = $minor / 100;
        $decimals = $major === floor($major) ? 0 : 2;

        return self::symbol($currency).number_format($major, $decimals);
    }

    /**
     * Full precision with a separated currency code, for receipts and audit
     * screens where the code matters more than the symbol.
     */
    public static function formatWithCode(int $minor, string $currency = 'NGN'): string
    {
        $major = $minor / 100;

        return strtoupper($currency).' '.number_format($major, $major === floor($major) ? 0 : 2);
    }
}
