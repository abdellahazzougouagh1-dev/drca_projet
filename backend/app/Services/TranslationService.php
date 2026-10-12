<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TranslationService
{
    private static array $journalDictionary = [
        'le matin' => 'الصباح',
        'matin' => 'الصباح',
        'l\'opinion' => 'العلم',
        'opinion' => 'العلم',
        'al bayane' => 'البيان',
        'bayane' => 'البيان',
        'liberation' => 'الاتحاد الاشتراكي',
        'libération' => 'الاتحاد الاشتراكي',
        'l\'economiste' => 'الإقتصادي',
        'l\'économiste' => 'الإقتصادي',
        'aujourd\'hui le maroc' => 'الأحداث المغربية',
        'finances news' => 'أخبار المالية',
        'les inspirations eco' => 'إلهامات اقتصادية',
    ];

    public static function translateFrToAr(string $text): string
    {
        $trim = trim($text);
        if (empty($trim)) {
            return '';
        }

        $lower = mb_strtolower($trim);
        if (isset(self::$journalDictionary[$lower])) {
            return self::$journalDictionary[$lower];
        }

        try {
            $response = Http::timeout(4)->get('https://translate.googleapis.com/translate_a/single', [
                'client' => 'gtx',
                'sl' => 'fr',
                'tl' => 'ar',
                'dt' => 't',
                'q' => $trim,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data[0]) && is_array($data[0])) {
                    $translatedParts = [];
                    foreach ($data[0] as $part) {
                        if (isset($part[0])) {
                            $translatedParts[] = $part[0];
                        }
                    }
                    return implode('', $translatedParts);
                }
            }
        } catch (\Throwable $e) {
            // Log or fallback
        }

        return $trim;
    }
}
