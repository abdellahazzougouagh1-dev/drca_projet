<?php

namespace App\Support;

use App\Models\Aoo;
use Carbon\Carbon;

class AooDocumentHelper
{
    public static function presentation(Aoo $aoo): array
    {
        return [
            'date_ouverture' => self::formatDate($aoo->date_ouverture),
            'date_lettre' => self::formatDate($aoo->date_lettre) !== '................'
                ? self::formatDate($aoo->date_lettre)
                : Carbon::now()->format('d/m/Y'),
            'heure_ouverture' => self::formatTime($aoo->heure_ouverture),
            'lieu_ouverture' => $aoo->lieu_ouverture
                ?: "Siège de la direction régionale du conseil agricole Rabat-Salé-Kénitra sis à angle avenue Mohamed V et Rue Sebta Kenitra",
            'ref_numero' => ($aoo->num_aoo_interne ?: '......') . '/DRCA-RSK/' . date('Y'),
        ];
    }

    public static function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '................';
        }

        try {
            if ($value instanceof Carbon) {
                return $value->format('d/m/Y');
            }

            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return '................';
        }
    }

    public static function formatTime(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '................';
        }

        $value = (string) $value;

        if (preg_match('/^\d{1,2}:\d{2}/', $value)) {
            return substr($value, 0, 5);
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable) {
            return $value;
        }
    }

    public static function embedImage(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        $path = public_path($relativePath);
        if (!file_exists($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }
}
