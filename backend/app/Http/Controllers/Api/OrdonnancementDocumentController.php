<?php

namespace App\Http\Controllers\Api;

use App\Helpers\NumberToWordsHelper;
use App\Http\Controllers\Controller;
use App\Models\Ordonnancement;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrdonnancementDocumentController extends Controller
{
    private function safeFileName($prefix, $ref, $extension = 'pdf')
    {
        $cleanRef = preg_replace('/[^A-Za-z0-9_\-]/', '_', $ref);
        return "{$prefix}_{$cleanRef}.{$extension}";
    }

    /**
     * Generate / Preview / Download document by type (op, ov, oi, etat_liquidation).
     */
    public function generate(Request $request, $id, $type, $ordreId = null)
    {
        $ordonnancement = Ordonnancement::with([
            'ordres',
            'fournisseur',
            'notificationLigne',
            'liquidation.pieces',
            'liquidation.lignes',
            'marche.aoo',
            'marche.lot',
            'consultation'
        ])->findOrFail($id);

        $ordre = null;
        if ($ordreId) {
            $ordre = $ordonnancement->ordres()->find($ordreId);
        }

        // If no specific ordre passed for OP, take first payment order
        if (!$ordre) {
            if ($type === 'op') {
                $ordre = $ordonnancement->ordres->where('type_mouvement', 'Paiement fournisseur')->first() 
                         ?: $ordonnancement->ordres->first();
            } elseif ($type === 'ov') {
                $ordre = $ordonnancement->ordres->where('type_mouvement', 'Retenue à la source TVA')->first()
                         ?: $ordonnancement->ordres->where('type_mouvement', 'Ordre de virement (OV)')->first()
                         ?: $ordonnancement->ordres->first();
            } elseif ($type === 'oi') {
                $ordre = $ordonnancement->ordres->where('type_mouvement', 'Retenue à la source IAS')->first()
                         ?: $ordonnancement->ordres->where('type_mouvement', 'Retenue à la source TVA')->first()
                         ?: $ordonnancement->ordres->first();
            }
        }

        $montant = $ordre ? (float)$ordre->montant : (float)$ordonnancement->net_a_payer;
        $beneficiaire = $ordre ? $ordre->beneficiaire : ($ordonnancement->beneficiaire_nom ?: $ordonnancement->fournisseur->raison_sociale ?? '');
        $rib = $ordre ? $ordre->rib_compte : ($ordonnancement->fournisseur->rib ?? '');
        $modePaiement = $ordre ? $ordre->mode_paiement : 'Virement';

        // Conversion en lettres françaises
        $montantLettres = NumberToWordsHelper::toFrenchWords($montant);

        $data = [
            'ordonnancement' => $ordonnancement,
            'ordre' => $ordre,
            'montant' => $montant,
            'montant_lettres' => $montantLettres,
            'beneficiaire' => $beneficiaire,
            'rib' => $rib,
            'mode_paiement' => $modePaiement,
            'liquidation' => $ordonnancement->liquidation,
            'marche' => $ordonnancement->marche,
            'fournisseur' => $ordonnancement->fournisseur,
        ];

        $isPreview = $request->boolean('preview') || $request->query('preview') == 'true' || $request->query('preview') == '1';

        switch ($type) {
            case 'op_ras':
            case 'op_ras_is':
                $ordreIs = $ordonnancement->ordres()->where(function($q) {
                    $q->where('type_mouvement', 'like', '%ias%')
                      ->orWhere('type_mouvement', 'like', '%is%')
                      ->orWhere('creance', 'like', '%ias%');
                })->first() ?: $ordre;
                if ($ordreIs && !$ordre) {
                    $data['ordre'] = $ordreIs;
                    $data['montant'] = (float)$ordreIs->montant;
                    $data['montant_lettres'] = NumberToWordsHelper::toFrenchWords((float)$ordreIs->montant);
                }
                request()->merge(['nature' => 'is']);
                $pdf = Pdf::loadView('pdf.ordonnancement.op_ras', $data);
                $filename = $this->safeFileName('Ordre_Paiement_RAS_IS', ($data['ordre']?->num_ordre ?? 'OP_RAS_IS') . '_' . $ordonnancement->num_ordonnancement);
                return $isPreview ? $pdf->stream($filename) : $pdf->download($filename);

            case 'op_ras_tva':
                $ordreTva = $ordonnancement->ordres()->where(function($q) {
                    $q->where('type_mouvement', 'like', '%tva%')
                      ->orWhere('creance', 'like', '%tva%');
                })->first() ?: $ordre;
                if ($ordreTva && !$ordre) {
                    $data['ordre'] = $ordreTva;
                    $data['montant'] = (float)$ordreTva->montant;
                    $data['montant_lettres'] = NumberToWordsHelper::toFrenchWords((float)$ordreTva->montant);
                }
                request()->merge(['nature' => 'tva']);
                $pdf = Pdf::loadView('pdf.ordonnancement.op_ras', $data);
                $filename = $this->safeFileName('Ordre_Paiement_RAS_TVA', ($data['ordre']?->num_ordre ?? 'OP_RAS_TVA') . '_' . $ordonnancement->num_ordonnancement);
                return $isPreview ? $pdf->stream($filename) : $pdf->download($filename);

            case 'op':
                $isRasOrder = $ordre && (
                    stripos($ordre->type_mouvement ?? '', 'retenue') !== false ||
                    stripos($ordre->type_mouvement ?? '', 'tva') !== false ||
                    stripos($ordre->type_mouvement ?? '', 'ias') !== false ||
                    stripos($ordre->creance ?? '', 'retenue') !== false
                );
                $viewName = $isRasOrder ? 'pdf.ordonnancement.op_ras' : 'pdf.ordonnancement.op';
                $pdf = Pdf::loadView($viewName, $data);
                $filename = $this->safeFileName('Ordre_Paiement', ($ordre->num_ordre ?? $ordonnancement->num_op ?: $ordonnancement->num_ordonnancement));
                return $isPreview ? $pdf->stream($filename) : $pdf->download($filename);

            case 'ov':
                $pdf = Pdf::loadView('pdf.ordonnancement.ov', $data);
                $filename = $this->safeFileName('Ordre_Virement', ($ordre->num_ordre ?? 'OV') . '_' . $ordonnancement->num_ordonnancement);
                return $isPreview ? $pdf->stream($filename) : $pdf->download($filename);

            case 'oi':
                $pdf = Pdf::loadView('pdf.ordonnancement.oi', $data);
                $filename = $this->safeFileName('Ordre_Imputation', ($ordre->num_ordre ?? 'OI') . '_' . $ordonnancement->num_ordonnancement);
                return $isPreview ? $pdf->stream($filename) : $pdf->download($filename);

            case 'etat_liquidation':
                $liq = $ordonnancement->liquidation;
                $montantTtc = (float) ($liq?->montant_brut_ttc ?: $liq?->montant_ttc ?: $ordonnancement->montant_brut ?: 0);
                $montantHt = (float) ($liq?->montant_brut_ht ?: $liq?->montant_ht ?: ($montantTtc / 1.20));
                $montantTva = (float) ($liq?->montant_tva ?: ($montantTtc - $montantHt));
                $montantRas = (float) ($ordonnancement->retenue_tva ?: $ordonnancement->ras_total ?: 0);
                $tauxRas = ($montantTva > 0 && $montantRas > 0) ? round(($montantRas / $montantTva) * 100) . '%' : ($montantRas > 0 ? '75%' : '0%');
                $netAVerser = (float) ($ordonnancement->net_a_payer ?: ($montantTtc - $montantRas));

                $factureRef = null;
                if ($liq && $liq->num_facture) {
                    $factureRef = "Facture N°" . $liq->num_facture . ($liq->date_facture ? " du " . Carbon::parse($liq->date_facture)->format('d/m/Y') : "");
                } elseif ($liq && $liq->num_decompte) {
                    $factureRef = "Décompte N°" . $liq->num_decompte . ($liq->date_decompte ? " du " . Carbon::parse($liq->date_decompte)->format('d/m/Y') : "");
                } else {
                    $factureRef = "Facture N°" . ($liq?->num_liquidation ?? '010/' . ($ordonnancement->exercice ?? date('Y'))) . " du " . Carbon::parse($ordonnancement->date_ordonnancement)->format('d/m/Y');
                }

                $liqData = [
                    'numMarche' => $ordonnancement->reference,
                    'objet' => $liq?->objet_liquidation ?? $ordonnancement->intitule_depense,
                    'factureRef' => $factureRef,
                    'beneficiaire' => $ordonnancement->beneficiaire_nom,
                    'montantTtc' => $montantTtc,
                    'montantEnLettres' => \App\Support\MontantEnLettres::convert($montantTtc),
                    'montantHt' => $montantHt,
                    'montantTva' => $montantTva,
                    'tauxRas' => $tauxRas,
                    'montantRas' => $montantRas,
                    'netAVerser' => $netAVerser,
                    'dateLiquidation' => $liq?->date_decompte ? Carbon::parse($liq->date_decompte)->format('d/m/Y') : Carbon::parse($ordonnancement->date_ordonnancement)->format('d/m/Y'),
                    'budget' => $ordonnancement->budget_type ?? 'Investissement',
                    'exercice' => $ordonnancement->exercice ?? date('Y'),
                    'numLiquidation' => $liq?->num_liquidation ?? $ordonnancement->num_ordonnancement
                ];

                $pdf = Pdf::loadView('pdf.liquidation.etat_liquidation', $liqData);
                $filename = $this->safeFileName('Etat_Liquidation', ($liq?->num_liquidation ?? $ordonnancement->num_ordonnancement));
                return $isPreview ? $pdf->stream($filename) : $pdf->download($filename);

            default:
                return response()->json(['error' => 'Type de document non reconnu'], 400);
        }
    }
}
