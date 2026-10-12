<?php

namespace App\Helpers;

class ChiffreEnLettre
{
    private static $unites = [
        0 => "zéro", 1 => "un", 2 => "deux", 3 => "trois", 4 => "quatre",
        5 => "cinq", 6 => "six", 7 => "sept", 8 => "huit", 9 => "neuf",
        10 => "dix", 11 => "onze", 12 => "douze", 13 => "treize", 14 => "quatorze",
        15 => "quinze", 16 => "seize", 17 => "dix-sept", 18 => "dix-huit", 19 => "dix-neuf"
    ];

    private static $dizaines = [
        2 => "vingt", 3 => "trente", 4 => "quarante", 5 => "cinquante", 
        6 => "soixante", 7 => "soixante-dix", 8 => "quatre-vingt", 9 => "quatre-vingt-dix"
    ];

    /**
     * Convertit un nombre décimal en toutes lettres en français.
     * Exemple: 658020.00 -> Six cent cinquante-huit mille vingt
     */
    public static function convertir(float $nombre): string
    {
        // Séparer la partie entière et la partie décimale
        $entier = (int) floor($nombre);
        $decimal = (int) round(($nombre - $entier) * 100);

        $lettres = ucfirst(self::convertirEntier($entier));

        if ($decimal > 0) {
            $lettres .= " et " . self::convertirEntier($decimal) . " centimes";
        }

        return $lettres;
    }

    private static function convertirEntier(int $n): string
    {
        if ($n < 20) {
            return self::$unites[$n];
        }

        if ($n < 100) {
            $d = (int) floor($n / 10);
            $u = $n % 10;
            
            if ($d == 7 || $d == 9) {
                $d--;
                $u += 10;
            }

            $result = self::$dizaines[$d];
            
            if ($u > 0) {
                if ($u == 1 && $d != 8) { // pas de "quatre-vingt et un"
                    $result .= " et un";
                } else {
                    $result .= "-" . self::$unites[$u];
                }
            }

            // Pluriel de "quatre-vingt" s'il n'est pas suivi d'un autre nombre
            if ($n == 80) {
                $result .= "s";
            }

            return $result;
        }

        if ($n < 1000) {
            $c = (int) floor($n / 100);
            $reste = $n % 100;
            
            $result = "";
            if ($c == 1) {
                $result = "cent";
            } else {
                $result = self::$unites[$c] . " cent";
                // Pluriel de "cent" s'il n'est pas suivi d'un autre nombre
                if ($reste == 0) {
                    $result .= "s";
                }
            }

            if ($reste > 0) {
                $result .= " " . self::convertirEntier($reste);
            }
            return $result;
        }

        if ($n < 1000000) {
            $m = (int) floor($n / 1000);
            $reste = $n % 1000;
            
            $result = "";
            if ($m == 1) {
                $result = "mille";
            } else {
                $result = self::convertirEntier($m) . " mille";
            }

            if ($reste > 0) {
                $result .= " " . self::convertirEntier($reste);
            }
            return $result;
        }

        if ($n < 1000000000) {
            $m = (int) floor($n / 1000000);
            $reste = $n % 1000000;
            
            $result = self::convertirEntier($m) . " million" . ($m > 1 ? "s" : "");

            if ($reste > 0) {
                $result .= " " . self::convertirEntier($reste);
            }
            return $result;
        }

        return (string) $n;
    }
}
