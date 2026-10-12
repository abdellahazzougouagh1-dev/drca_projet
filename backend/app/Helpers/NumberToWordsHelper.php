<?php

namespace App\Helpers;

class NumberToWordsHelper
{
    private static array $frUnits = [
        0 => '', 1 => 'un', 2 => 'deux', 3 => 'trois', 4 => 'quatre',
        5 => 'cinq', 6 => 'six', 7 => 'sept', 8 => 'huit', 9 => 'neuf',
        10 => 'dix', 11 => 'onze', 12 => 'douze', 13 => 'treize', 14 => 'quatorze',
        15 => 'quinze', 16 => 'seize', 17 => 'dix-sept', 18 => 'dix-huit', 19 => 'dix-neuf'
    ];

    private static array $frTens = [
        0 => '', 1 => 'dix', 2 => 'vingt', 3 => 'trente', 4 => 'quarante',
        5 => 'cinquante', 6 => 'soixante', 7 => 'soixante-dix', 8 => 'quatre-vingts', 9 => 'quatre-vingt-dix'
    ];

    public static function toFrenchWords(float $number): string
    {
        $integerPart = (int) floor($number);
        if ($integerPart === 0) {
            return 'zéro';
        }

        return ucfirst(self::convertIntegerToFrenchWords($integerPart));
    }

    public static function toFrenchMoneyWords(float $amount, string $currency = 'dirhams', string $subCurrency = 'centimes'): string
    {
        $rounded = round($amount, 2);
        $integerPart = (int) floor($rounded);
        $decimalPart = (int) round(($rounded - $integerPart) * 100);

        $words = $integerPart === 0 ? 'zéro' : self::convertIntegerToFrenchWords($integerPart);
        $result = $words . ' ' . $currency;

        if ($decimalPart > 0) {
            $decimalWords = self::convertIntegerToFrenchWords($decimalPart);
            $result .= ' et ' . $decimalWords . ' ' . ($decimalPart === 1 ? 'centime' : $subCurrency);
        }

        return ucfirst(trim($result));
    }

    private static function convertIntegerToFrenchWords(int $n): string
    {
        if ($n < 20) {
            return self::$frUnits[$n];
        }

        if ($n < 100) {
            $tens = (int) floor($n / 10);
            $units = $n % 10;

            if ($tens === 7) {
                return 'soixante' . ($units === 1 ? ' et onze' : '-' . self::$frUnits[10 + $units]);
            }
            if ($tens === 9) {
                return 'quatre-vingt-' . self::$frUnits[10 + $units];
            }
            if ($tens === 8 && $units === 0) {
                return 'quatre-vingts';
            }

            $sep = ($units === 1 && $tens !== 8) ? ' et ' : '-';
            return self::$frTens[$tens] . ($units > 0 ? $sep . self::$frUnits[$units] : '');
        }

        if ($n < 1000) {
            $hundreds = (int) floor($n / 100);
            $rest = $n % 100;
            $hStr = ($hundreds === 1) ? 'cent' : self::$frUnits[$hundreds] . ' cent' . ($rest === 0 ? 's' : '');
            return $hStr . ($rest > 0 ? ' ' . self::convertIntegerToFrenchWords($rest) : '');
        }

        if ($n < 1000000) {
            $thousands = (int) floor($n / 1000);
            $rest = $n % 1000;
            $tStr = ($thousands === 1) ? 'mille' : self::convertIntegerToFrenchWords($thousands) . ' mille';
            return $tStr . ($rest > 0 ? ' ' . self::convertIntegerToFrenchWords($rest) : '');
        }

        if ($n < 1000000000) {
            $millions = (int) floor($n / 1000000);
            $rest = $n % 1000000;
            $mStr = ($millions === 1) ? 'un million' : self::convertIntegerToFrenchWords($millions) . ' millions';
            return $mStr . ($rest > 0 ? ' ' . self::convertIntegerToFrenchWords($rest) : '');
        }

        return (string) $n;
    }

    public static function toArabicWords(float $number): string
    {
        $integerPart = (int) floor($number);
        if ($integerPart === 0) {
            return 'صفر';
        }

        return self::convertIntegerToArabicWords($integerPart);
    }

    private static function convertIntegerToArabicWords(int $n): string
    {
        $ones = [
            0 => '', 1 => 'واحد', 2 => 'اثنان', 3 => 'ثلاثة', 4 => 'أربعة',
            5 => 'خمسة', 6 => 'ستة', 7 => 'سبعة', 8 => 'ثمانية', 9 => 'تسعة',
            10 => 'عشرة', 11 => 'أحد عشر', 12 => 'اثنا عشر', 13 => 'ثلاثة عشر',
            14 => 'أربعة عشر', 15 => 'خمسة عشر', 16 => 'ستة عشر', 17 => 'سبعة عشر',
            18 => 'ثمانية عشر', 19 => 'تسعة عشر'
        ];

        $tens = [
            2 => 'عشرون', 3 => 'ثلاثون', 4 => 'أربعون', 5 => 'خمسون',
            6 => 'ستون', 7 => 'سبعون', 8 => 'ثمانون', 9 => 'تسعون'
        ];

        $hundreds = [
            0 => '', 1 => 'مائة', 2 => 'مائتان', 3 => 'ثلاثمائة', 4 => 'أربعمائة',
            5 => 'خمسمائة', 6 => 'ستمائة', 7 => 'سبعمائة', 8 => 'ثمانمائة', 9 => 'تسعمائة'
        ];

        if ($n < 20) {
            return $ones[$n];
        }

        if ($n < 100) {
            $t = (int) floor($n / 10);
            $u = $n % 10;
            if ($u === 0) {
                return $tens[$t];
            }
            return $ones[$u] . ' و' . $tens[$t];
        }

        if ($n < 1000) {
            $h = (int) floor($n / 100);
            $r = $n % 100;
            if ($r === 0) {
                return $hundreds[$h];
            }
            return $hundreds[$h] . ' و' . self::convertIntegerToArabicWords($r);
        }

        if ($n < 1000000) {
            $th = (int) floor($n / 1000);
            $r = $n % 1000;
            $thWord = '';
            if ($th === 1) {
                $thWord = 'ألف';
            } elseif ($th === 2) {
                $thWord = 'ألفان';
            } elseif ($th >= 3 && $th <= 10) {
                $thWord = self::convertIntegerToArabicWords($th) . ' آلاف';
            } else {
                $thWord = self::convertIntegerToArabicWords($th) . ' ألفاً';
            }

            if ($r === 0) {
                return $thWord;
            }
            return $thWord . ' و' . self::convertIntegerToArabicWords($r);
        }

        if ($n < 1000000000) {
            $m = (int) floor($n / 1000000);
            $r = $n % 1000000;
            $mWord = '';
            if ($m === 1) {
                $mWord = 'مليون';
            } elseif ($m === 2) {
                $mWord = 'مليونان';
            } elseif ($m >= 3 && $m <= 10) {
                $mWord = self::convertIntegerToArabicWords($m) . ' ملايين';
            } else {
                $mWord = self::convertIntegerToArabicWords($m) . ' مليونا';
            }

            if ($r === 0) {
                return $mWord;
            }
            return $mWord . ' و' . self::convertIntegerToArabicWords($r);
        }

        return (string) $n;
    }
}
