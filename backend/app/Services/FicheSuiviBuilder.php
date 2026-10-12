<?php

namespace App\Services;

use App\Models\Aoo;
use App\Support\AooDocumentHelper;
use Carbon\Carbon;

class FicheSuiviBuilder
{
    public static function build(Aoo $aoo): array
    {
        $aoo->loadMissing([
            'lots.attributaire',
            'lots.decisions.fournisseur',
            'concurrents.fournisseur',
        ]);

        $lotsData = LotEstimationBuilder::buildForAoo($aoo);
        $piecesDossier = [
            'DECISION DE COP',
            'LETTRE DE COP',
            'JOURNAL DE PUB',
            'CPS',
            'RC',
            'Estimation',
            'PV de la commission',
            'PV de jugement',
            "PV de l'ouverture des plis",
            "Rapport d'analyse",
            "Lettre d'attribution",
            'Contrat / Bon de commande',
        ];

        return [
            'exercice' => self::resolveExercice($aoo),
            'date_ouverture' => self::formatDateForPdf($aoo->date_ouverture),
            'heure_ouverture' => self::formatHeureForPdf($aoo->heure_ouverture),
            'lots' => $lotsData->map(fn (array $lotData) => [
                'num_lot' => $lotData['lot']->num_lot ?? 'LOT 1',
                'objet_lot' => $lotData['lot']->objet_lot ?? $aoo->objet,
                'estimation_ttc' => $lotData['total_ttc'],
            ])->values()->all(),
            'nombre_lots' => max($lotsData->count(), (int) ($aoo->nombre_lots ?? 1)),
            'resultats' => self::resolveResultats($aoo),
            'pieces_dossier' => $piecesDossier,
        ];
    }

    private static function resolveExercice(Aoo $aoo): string
    {
        if (preg_match('/\/(\d{4})\//', (string) $aoo->num_aoo, $matches)) {
            return $matches[1];
        }

        if ($aoo->date_ouverture) {
            return Carbon::parse($aoo->date_ouverture)->format('Y');
        }

        if ($aoo->created_at) {
            return $aoo->created_at->format('Y');
        }

        return date('Y');
    }

    private static function formatDateForPdf(mixed $value): string
    {
        $formatted = AooDocumentHelper::formatDate($value);

        return $formatted === '................' ? '' : $formatted;
    }

    private static function formatHeureForPdf(mixed $value): string
    {
        $formatted = AooDocumentHelper::formatTime($value);

        if ($formatted === '................' || $formatted === '') {
            return '';
        }

        return strlen($formatted) === 5 ? $formatted . ':00' : $formatted;
    }

    private static function resolveResultats(Aoo $aoo): array
    {
        $resultats = [];

        if ($aoo->lots->count() > 1) {
            foreach ($aoo->lots->sortBy('id') as $lot) {
                $societe = $lot->attributaire?->raison_sociale;
                $montant = null;

                if ($lot->attributaire_fournisseur_id) {
                    $decision = $lot->decisions->first(
                        fn ($d) => (int) $d->fournisseur_id === (int) $lot->attributaire_fournisseur_id
                            && strcasecmp((string) $d->statut, 'Retenu') === 0
                    );
                    $montant = $decision?->montant_propose;
                }

                if (!$societe) {
                    continue;
                }

                $resultats[] = [
                    'lot' => $lot->num_lot,
                    'societe' => $societe,
                    'montant' => $montant,
                ];
            }

            return $resultats;
        }

        $lot = $aoo->lots->first();
        if ($lot?->attributaire) {
            $decision = $lot->decisions->first(
                fn ($d) => (int) $d->fournisseur_id === (int) $lot->attributaire_fournisseur_id
                    && strcasecmp((string) $d->statut, 'Retenu') === 0
            );

            return [[
                'lot' => $lot->num_lot,
                'societe' => $lot->attributaire->raison_sociale,
                'montant' => $decision?->montant_propose,
            ]];
        }

        $retenu = $aoo->concurrents->first(
            fn ($c) => strcasecmp((string) $c->statut_analyse, 'retenu') === 0
        );

        if (!$retenu) {
            return [];
        }

        return [[
            'lot' => $lot?->num_lot ?? 'LOT 1',
            'societe' => $retenu->fournisseur?->raison_sociale ?? $retenu->nom_soumissionnaire,
            'montant' => $retenu->montant_engagement,
        ]];
    }
}
