<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Services\LotEstimationBuilder;
use Barryvdh\DomPDF\Facade\Pdf;

class LotController extends Controller
{
    public function exportEstimation(Lot $lot)
    {
        $lotData = LotEstimationBuilder::build($lot);
        $aoo = $lot->aoo;

        if (!$aoo) {
            return response()->json(['error' => 'AOO introuvable pour ce lot.'], 404);
        }

        $pdf = Pdf::loadView('pdf.aoo.estimation', [
            'aoo' => $aoo,
            'lotsData' => collect([$lotData]),
        ]);

        $safeLot = str_replace(['/', ' ', ':'], '_', $lot->num_lot ?? 'LOT');
        $safeAoo = str_replace('/', '_', $aoo->num_aoo);

        return $pdf->download("Estimation_{$safeLot}_AOO_{$safeAoo}.pdf");
    }
}
