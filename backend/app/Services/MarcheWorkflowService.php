<?php

namespace App\Services;

class MarcheWorkflowService
{
    public static function phases(): array
    {
        return [
            [
                'key' => 'consultation',
                'label' => 'Consultation',
                'inProgressStatus' => 'consultation_en_cours',
                'doneStatus' => 'consultation_validee',
            ],
            [
                'key' => 'engagement',
                'label' => 'Engagement',
                'inProgressStatus' => 'engagement_en_cours',
                'doneStatus' => 'engagement_validee',
            ],
            [
                'key' => 'liquidation',
                'label' => 'Liquidation',
                'inProgressStatus' => 'liquidation_en_cours',
                'doneStatus' => 'liquidation_validee',
            ],
            [
                'key' => 'ordonnancement',
                'label' => 'Ordonnancement',
                'inProgressStatus' => 'ordonnancement_en_cours',
                'doneStatus' => 'ordonnancement_validee',
            ],
            [
                'key' => 'cloture',
                'label' => 'Clôture',
                'inProgressStatus' => 'cloture_en_cours',
                'doneStatus' => 'cloture_validee',
            ],
        ];
    }

    public static function resolveStatus(string $phase, bool $validated = false): string
    {
        return $validated ? sprintf('%s_validee', $phase) : sprintf('%s_en_cours', $phase);
    }

    public static function currentPhase(?string $status): string
    {
        $status = (string) ($status ?? '');

        if ($status === 'cloture' || $status === 'cloture_validee' || $status === 'cloture_en_cours') {
            return 'cloture';
        }
        if ($status === 'ordonnancement_validee') {
            return 'ordonnancement';
        }

        foreach (self::phases() as $phase) {
            if ($status === $phase['inProgressStatus'] || $status === $phase['doneStatus']) {
                return $phase['key'];
            }
        }

        return 'consultation';
    }

    public static function progressPercent(?string $status): int
    {
        $status = (string) ($status ?? '');

        return match ($status) {
            'consultation_en_cours', 'consultation_validee' => 20,
            'engagement_en_cours', 'engagement_validee' => 40,
            'liquidation_en_cours', 'liquidation_validee' => 60,
            'ordonnancement_en_cours', 'ordonnancement_validee' => 80,
            'cloture_en_cours', 'cloture_validee', 'cloture' => 100,
            default => 0,
        };
    }
}
