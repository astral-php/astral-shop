<?php

declare(strict_types=1);

namespace App\Support;

final class Money
{
    /** Affiche un montant TTC en centimes (ex. 2490 → « 24,90 € »). */
    public static function formatCents(int $cents, string $symbol = '€'): string
    {
        $neg = $cents < 0;
        $cents = abs($cents);
        $euros = intdiv($cents, 100);
        $rem   = $cents % 100;
        $out   = number_format($euros, 0, ',', ' ') . ',' . sprintf('%02d', $rem) . "\u{00a0}" . $symbol;

        return $neg ? '-' . $out : $out;
    }

    /** Convertit une saisie admin « 24,90 » ou « 24.90 » en centimes TTC. */
    public static function parseEurosToCents(string $input): ?int
    {
        $input = trim(str_replace([' ', "\u{00a0}", '€'], '', $input));
        if ($input === '') {
            return null;
        }

        $input = str_replace(',', '.', $input);
        if (!is_numeric($input)) {
            return null;
        }

        $euros = (float) $input;
        if ($euros < 0) {
            return null;
        }

        return (int) round($euros * 100);
    }
}
