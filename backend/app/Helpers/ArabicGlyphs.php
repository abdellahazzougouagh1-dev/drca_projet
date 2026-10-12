<?php

namespace App\Helpers;

if (file_exists(__DIR__ . '/../../vendor/ar-php/ar-php/I18N/Arabic.php')) {
    require_once __DIR__ . '/../../vendor/ar-php/ar-php/I18N/Arabic.php';
}

class ArabicGlyphs
{
    private static ?\I18N_Arabic $arabic = null;

    public static function utf8Glyphs(string $text): string
    {
        if (empty(trim($text))) {
            return '';
        }

        if (self::$arabic === null) {
            if (class_exists('I18N_Arabic')) {
                self::$arabic = new \I18N_Arabic('Glyphs');
            }
        }

        if (self::$arabic) {
            return self::$arabic->utf8Glyphs($text);
        }

        return $text;
    }
}
