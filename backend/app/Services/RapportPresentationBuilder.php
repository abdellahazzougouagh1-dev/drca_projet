<?php

namespace App\Services;

use App\Models\Aoo;
use App\Models\Fournisseur;
use App\Models\Lot;
use App\Models\Marche;
use App\Support\AooDocumentHelper;
use App\Support\MontantEnLettres;

class RapportPresentationBuilder
{
    /**
     * Construit les données du Rapport de Présentation sans eager-loading
     * sur la relation "lot" (conflit avec la colonne string marches.lot).
     */
    public static function build(Marche $marche): array
    {
        // Étape A : marché déjà chargé
        // Étape B : lot associé via lot_id
        $lot = null;
        if (!empty($marche->lot_id)) {
            $lot = Lot::query()->find($marche->lot_id);
        }

        // Étape C : AOO parent
        $aoo = null;
        if (!empty($marche->aoo_id)) {
            $aoo = Aoo::query()->find($marche->aoo_id);
        }

        // Étape D : entreprise adjudicataire
        $fournisseur = null;
        if (!empty($marche->fournisseur_id)) {
            $fournisseur = Fournisseur::query()->find($marche->fournisseur_id);
        }

        // Étape E : budget + montants
        $estimationTtc = self::resolveEstimationTtc($lot, $aoo);
        $montantMarche = round((float) ($marche->montant ?? 0), 2);

        $dateAoo = AooDocumentHelper::formatDate($aoo?->date_ouverture);
        if ($dateAoo === '................') {
            $dateAoo = '';
        }

        $adresseParts = array_filter([
            $fournisseur?->adresse,
            $fournisseur?->ville,
        ]);

        $rubriqueParts = preg_split('/\s+/', trim((string) ($marche->code_budget ?? '')), 3) ?: [];
        $rubriqueParts = array_pad($rubriqueParts, 3, '');

        $lotLabel = trim((string) ($marche->getAttributes()['lot'] ?? $lot?->num_lot ?? ''));

        return [
            'num_marche' => $marche->num_marche ?? '',
            'num_aoo' => $aoo?->num_aoo ?? '',
            'date_aoo' => $dateAoo,
            'attributaire' => $marche->titulaire ?: '—',
            'attributaire_adresse' => $adresseParts ? implode(', ', $adresseParts) : '—',
            'objet_marche' => $marche->objet_marche
                ?: $lot?->objet_lot
                ?: $aoo?->objet
                ?: '—',
            'estimation_ttc_formate' => number_format($estimationTtc, 2, ',', ' '),
            'montant_marche_ttc_formate' => number_format($montantMarche, 2, ',', ' '),
            'montant_marche_lettres' => MontantEnLettres::convert($montantMarche),
            'lot_mention' => self::resolveLotMention($aoo, $lotLabel),
            'type_budget' => $marche->type_budget ?: 'Investissement',
            'intitule_budget' => $marche->intitule_budget ?: '—',
            'rubrique_1' => $rubriqueParts[0],
            'rubrique_2' => $rubriqueParts[1],
            'rubrique_3' => $rubriqueParts[2],
            'date_document' => now()->format('d/m/Y'),
            'delai_execution' => '12 mois',
        ];
    }

    private static function resolveEstimationTtc(?Lot $lot, ?Aoo $aoo): float
    {
        if ($lot) {
            $lot->load('items');
            $data = LotEstimationBuilder::build($lot);

            return (float) ($data['total_ttc'] ?? 0);
        }

        if ($aoo) {
            return round((float) ($aoo->budget ?? 0), 2);
        }

        return 0.0;
    }

    private static function resolveLotMention(?Aoo $aoo, string $lotLabel): string
    {
        $lotsCount = $aoo ? Lot::query()->where('aoo_id', $aoo->id)->count() : 0;
        $lotLabel = strtolower($lotLabel);

        if ($lotsCount <= 1 || str_contains($lotLabel, 'unique')) {
            return 'lot unique';
        }

        return $lotLabel !== '' ? strtoupper($lotLabel) : 'lot unique';
    }
}
