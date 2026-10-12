<?php

namespace App\Services;

use App\Models\Aoo;
use App\Models\ConcurrentLotDecision;
use App\Models\Lot;
use App\Models\OuverturePlisConcurrent;
use App\Support\AooDocumentHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LettreNotificationBuilder
{
    public static function collectForAoo(Aoo $aoo): array
    {
        $aoo->loadMissing(['lots', 'concurrents.fournisseur']);

        $lettres = [];

        if ($aoo->lots->count() > 1) {
            $decisions = ConcurrentLotDecision::query()
                ->where('aoo_id', $aoo->id)
                ->whereNotNull('statut')
                ->whereIn('statut', ['Retenu', 'Ecarte'])
                ->orderBy('lot_id')
                ->orderBy('fournisseur_id')
                ->get();

            foreach ($decisions as $decision) {
                $lettres[] = self::build($aoo, (int) $decision->fournisseur_id, (int) $decision->lot_id);
            }

            return $lettres;
        }

        $lot = self::resolveLot($aoo, null);
        if (!$lot) {
            return [];
        }

        foreach ($aoo->concurrents as $concurrent) {
            if (!in_array($concurrent->statut_analyse, ['retenu', 'ecarte'], true)) {
                continue;
            }

            $lettres[] = self::build($aoo, (int) $concurrent->fournisseur_id, (int) $lot->id);
        }

        return $lettres;
    }

    public static function build(Aoo $aoo, int $fournisseurId, int $lotId): array
    {
        $aoo->loadMissing(['lots', 'concurrents.fournisseur']);

        $lot = self::resolveLot($aoo, $lotId);
        if (!$lot) {
            throw (new ModelNotFoundException())->setModel(Lot::class, [$lotId]);
        }

        $concurrent = $aoo->concurrents->firstWhere('fournisseur_id', $fournisseurId);
        if (!$concurrent) {
            throw (new ModelNotFoundException())->setModel(OuverturePlisConcurrent::class, [$fournisseurId]);
        }

        $decision = self::resolveDecision($aoo, $concurrent, $lot);
        if (!$decision) {
            throw new \InvalidArgumentException('Aucune decision d\'analyse enregistree pour ce soumissionnaire sur ce lot.');
        }

        $admis = $decision['admis'];
        $dateOuverture = AooDocumentHelper::formatDate($aoo->date_ouverture);
        if ($dateOuverture === '................') {
            $dateOuverture = '';
        }

        $societe = $concurrent->fournisseur?->raison_sociale ?? $concurrent->nom_soumissionnaire ?? '';
        $lotNumero = $lot->num_lot ?? 'LOT 1';
        $lotObjet = $lot->objet_lot ?? $aoo->objet ?? '';
        $objetComplet = trim((string) $aoo->objet) . ' - LOT N° ' . $lotNumero . ' : ' . trim((string) $lotObjet);

        return [
            'admis' => $admis,
            'objet_lettre' => $admis ? "Lettre d'admission" : "Lettre d'écartement",
            'reference' => "Appel d'offre N° {$aoo->num_aoo} du {$dateOuverture}",
            'objet_complet' => $objetComplet,
            'societe' => $societe,
            'signataire' => $concurrent->signataire_titre ?: "Le Président de la commission d'appel d'offre",
            'gerant' => $concurrent->gerant_nom ?: 'Mme/Mr Le Gérant de la Société',
            'motif_ecartement' => $decision['motif_ecartement'] ?? '',
            'montant' => $decision['montant'] ?? null,
            'fournisseur_id' => $fournisseurId,
            'lot_id' => $lot->id,
            'lot_numero' => $lotNumero,
        ];
    }

    private static function resolveLot(Aoo $aoo, ?int $lotId): ?Lot
    {
        if ($lotId) {
            return $aoo->lots->firstWhere('id', $lotId);
        }

        return $aoo->lots->sortBy('id')->first();
    }

    private static function resolveDecision(Aoo $aoo, OuverturePlisConcurrent $concurrent, Lot $lot): ?array
    {
        if ($aoo->lots->count() > 1) {
            $decision = ConcurrentLotDecision::query()
                ->where('aoo_id', $aoo->id)
                ->where('lot_id', $lot->id)
                ->where('fournisseur_id', $concurrent->fournisseur_id)
                ->first();

            if (!$decision || !$decision->statut) {
                return null;
            }

            $admis = strcasecmp((string) $decision->statut, 'Retenu') === 0;

            return [
                'admis' => $admis,
                'motif_ecartement' => $decision->motif_ecartement,
                'montant' => $decision->montant_propose,
            ];
        }

        if (!in_array($concurrent->statut_analyse, ['retenu', 'ecarte'], true)) {
            return null;
        }

        return [
            'admis' => $concurrent->statut_analyse === 'retenu',
            'motif_ecartement' => $concurrent->motif_ecartement,
            'montant' => $concurrent->montant_engagement,
        ];
    }
}
