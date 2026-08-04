<?php

namespace App\Support;

class MontantEnLettres
{
    private const UNITS = [
        '', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
        'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf',
    ];

    private const TENS = [
        '', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt',
    ];

    public static function convert(float $montant): string
    {
        $montant = round(abs($montant), 2);
        $entiers = (int) floor($montant);
        $centimes = (int) round(($montant - $entiers) * 100);

        if ($centimes >= 100) {
            $entiers += 1;
            $centimes = 0;
        }

        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter('fr_FR', \NumberFormatter::SPELLOUT);
            $motsEntiers = $formatter->format($entiers);
            if ($motsEntiers !== false) {
                $motsEntiers = self::normalize($motsEntiers);
                $labelDirhams = $entiers > 1 ? 'dirhams' : 'dirham';

                if ($centimes > 0) {
                    $motsCentimes = $formatter->format($centimes);
                    if ($motsCentimes !== false) {
                        $motsCentimes = self::normalize($motsCentimes);
                        $labelCentimes = $centimes > 1 ? 'centimes' : 'centime';

                        return ucfirst("{$motsEntiers} {$labelDirhams} et {$motsCentimes} {$labelCentimes}");
                    }
                }

                return ucfirst("{$motsEntiers} {$labelDirhams}");
            }
        }

        $motsEntiers = self::spellInteger($entiers);
        $labelDirhams = $entiers > 1 ? 'dirhams' : 'dirham';

        if ($centimes > 0) {
            $motsCentimes = self::spellInteger($centimes);
            $labelCentimes = $centimes > 1 ? 'centimes' : 'centime';

            return ucfirst("{$motsEntiers} {$labelDirhams} et {$motsCentimes} {$labelCentimes}");
        }

        return ucfirst("{$motsEntiers} {$labelDirhams}");
    }

    private static function spellInteger(int $number): string
    {
        if ($number === 0) {
            return 'zéro';
        }

        $parts = [];

        if ($number >= 1_000_000) {
            $millions = intdiv($number, 1_000_000);
            $parts[] = $millions === 1 ? 'un million' : self::spellBelowThousand($millions) . ' millions';
            $number %= 1_000_000;
        }

        if ($number >= 1000) {
            $thousands = intdiv($number, 1000);
            $parts[] = $thousands === 1 ? 'mille' : self::spellBelowThousand($thousands) . ' mille';
            $number %= 1000;
        }

        if ($number > 0) {
            $parts[] = self::spellBelowThousand($number);
        }

        return implode(' ', $parts);
    }

    private static function spellBelowThousand(int $number): string
    {
        if ($number < 20) {
            return self::UNITS[$number];
        }

        if ($number < 100) {
            $ten = intdiv($number, 10);
            $unit = $number % 10;

            if ($ten === 7 || $ten === 9) {
                $base = $ten === 7 ? 'soixante' : 'quatre-vingt';
                $remainder = $ten === 7 ? 10 + $unit : 10 + $unit;

                return $remainder === 11 && $ten === 7
                    ? 'soixante et onze'
                    : $base . '-' . self::UNITS[$remainder];
            }

            if ($unit === 0) {
                return $ten === 8 ? 'quatre-vingts' : self::TENS[$ten];
            }

            if ($unit === 1 && ($ten === 2 || $ten === 3 || $ten === 4 || $ten === 5 || $ten === 6)) {
                return self::TENS[$ten] . ' et un';
            }

            return self::TENS[$ten] . '-' . self::UNITS[$unit];
        }

        $hundreds = intdiv($number, 100);
        $remainder = $number % 100;
        $hundredWord = $hundreds === 1 ? 'cent' : self::UNITS[$hundreds] . ' cent';

        if ($remainder === 0 && $hundreds > 1) {
            $hundredWord .= 's';
        }

        return $remainder > 0
            ? $hundredWord . ' ' . self::spellBelowThousand($remainder)
            : $hundredWord;
    }

    private static function normalize(string $words): string
    {
        return preg_replace('/\s+/', ' ', str_replace(['‑', '-'], ' ', trim($words))) ?? trim($words);
    }
}
