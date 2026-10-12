<?php

namespace App\Services;

use App\Models\Aoo;
use App\Models\Lot;
use App\Support\MontantEnLettres;
use Illuminate\Support\Collection;

class LotEstimationBuilder
{
    public static function buildForAoo(Aoo $aoo): Collection
    {
        $aoo->loadMissing(['lots.items']);
        $lots = $aoo->lots->sortBy('id')->values();

        if ($lots->isEmpty()) {
            return collect([self::buildVirtualLotData($aoo)]);
        }

        return $lots->map(fn (Lot $lot) => self::build($lot));
    }

    public static function build(Lot $lot): array
    {
        $lot->loadMissing(['items', 'aoo']);
        $items = $lot->items->sortBy('numero')->values();
        $totalHt = round($items->sum(fn ($item) => (float) $item->montant_ht), 2);

        if ($totalHt <= 0 && $lot->estimation) {
            $totalTtc = round((float) $lot->estimation, 2);
            $totalHt = round($totalTtc / 1.20, 2);
            $totalTva = round($totalTtc - $totalHt, 2);
        } else {
            $totalTva = round($totalHt * 0.20, 2);
            $totalTtc = round($totalHt + $totalTva, 2);
        }

        return [
            'lot' => $lot,
            'items' => $items,
            'total_ht' => $totalHt,
            'total_tva' => $totalTva,
            'total_ttc' => $totalTtc,
            'montant_lettres' => MontantEnLettres::convert($totalTtc),
        ];
    }

    private static function buildVirtualLotData(Aoo $aoo): array
    {
        $budgetTtc = round((float) ($aoo->budget ?? 0), 2);
        $totalHt = $budgetTtc > 0 ? round($budgetTtc / 1.20, 2) : 0.0;
        $totalTva = $budgetTtc > 0 ? round($budgetTtc - $totalHt, 2) : 0.0;

        return [
            'lot' => (object) [
                'num_lot' => 'LOT 1',
                'objet_lot' => $aoo->objet,
            ],
            'items' => collect(),
            'total_ht' => $totalHt,
            'total_tva' => $totalTva,
            'total_ttc' => $budgetTtc,
            'montant_lettres' => MontantEnLettres::convert($budgetTtc),
        ];
    }
}
