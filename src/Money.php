<?php

declare(strict_types=1);

namespace App;

/**
 * Formats and parses the amounts shown across the app, using the 'money' block
 * of config/config.php (symbol, decimals, separators).
 */
final class Money
{
    /** @param array<string,mixed> $config The 'money' config array. */
    public function __construct(private array $config)
    {
    }

    /** "Rp 250.000" — with the sign kept for negative amounts. */
    public function format(float $amount, bool $withSymbol = true): string
    {
        $number = number_format(
            abs($amount),
            (int) $this->config['decimals'],
            (string) $this->config['dec_point'],
            (string) $this->config['thousands']
        );

        $sign = $amount < 0 ? '-' : '';

        return $withSymbol
            ? $sign . $this->config['symbol'] . ' ' . $number
            : $sign . $number;
    }

    /** "+Rp 250.000" / "-Rp 50.000" — for ledger rows, where direction matters. */
    public function signed(float $amount): string
    {
        return ($amount > 0 ? '+' : '') . $this->format($amount);
    }

    /**
     * Read an amount typed by a human: "250.000", "250,000", "Rp 250000" and
     * "250000.50" all work. Returns null when nothing numeric is left.
     */
    public function parse(string $input): ?float
    {
        $raw = preg_replace('/[^0-9,.\-]/', '', $input) ?? '';
        if ($raw === '' || $raw === '-') {
            return null;
        }

        $dec = (string) $this->config['dec_point'];
        $tho = (string) $this->config['thousands'];

        // Strip thousands separators first, then normalise the decimal point.
        $raw = str_replace($tho, '', $raw);
        $raw = str_replace($dec, '.', $raw);
        // Any separator left over (e.g. a stray "." in "1.234.567") is noise.
        if (substr_count($raw, '.') > 1) {
            $raw = str_replace('.', '', $raw);
        }

        return is_numeric($raw) ? (float) $raw : null;
    }
}
