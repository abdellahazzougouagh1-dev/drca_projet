<?php

namespace App\Services;

use App\Models\CommissionMembre;
use App\Models\Marche;
use App\Support\AooDocumentHelper;
use App\Support\MontantEnLettres;
use Carbon\Carbon;

class MarcheDocumentBuilder
{
    public static function presentation(Marche $marche): array
    {
        $marche->loadMissing(['aoo', 'fournisseur']);

        $lotModel = !empty($marche->lot_id)
            ? \App\Models\Lot::query()->find($marche->lot_id)
            : null;

        $fournisseur = $marche->fournisseur;
        $montant = (float) ($marche->montant ?? 0);
        $directeur = self::resolveDirecteurRegional();
        $dateAoo = AooDocumentHelper::formatDate($marche->aoo?->date_ouverture);
        if ($dateAoo === '................') {
            $dateAoo = '';
        }

        $lotLabel = trim((string) ($marche->getAttributes()['lot'] ?? $lotModel?->num_lot ?? ''));

        return [
            'num_marche' => $marche->num_marche,
            'num_aoo' => $marche->aoo?->num_aoo,
            'titulaire' => $marche->titulaire,
            'lot' => $lotLabel,
            'objet_marche' => $marche->objet_marche ?: $lotModel?->objet_lot ?: $marche->aoo?->objet,
            'qualite_gerant' => $marche->qualite_gerant ?: $fournisseur?->qualite_representant ?: 'Gérant',
            'representant' => $fournisseur?->representant,
            'nom_gerant' => $fournisseur?->representant ?: '',
            'qualite_responsable' => $marche->qualite_gerant ?: $fournisseur?->qualite_representant ?: 'Gérant',
            'nom_entreprise' => $marche->titulaire,
            'adresse_entreprise' => $fournisseur?->adresse ?: '',
            'ville_rc' => $fournisseur?->ville ?: '',
            'num_rc' => $fournisseur?->rc ?: '',
            'patente' => $fournisseur?->patente ?: '',
            'cnss' => $fournisseur?->cnss ?: '',
            'if' => $fournisseur?->if ?: '',
            'ice' => $fournisseur?->ice ?: '',
            'titulaire_compte' => $fournisseur?->titulaire_compte ?: $marche->titulaire,
            'banque' => $fournisseur?->banque ?: '',
            'agence_bancaire' => $fournisseur?->agence_bancaire ?: '',
            'rib' => $fournisseur?->rib ?: '',
            'montant' => $montant,
            'montant_formate' => number_format($montant, 2, ',', ' '),
            'montant_lettres' => MontantEnLettres::convert($montant),
            'date_signature' => $marche->date_signature
                ? $marche->date_signature->format('d/m/Y')
                : '',
            'date_approbation' => $marche->date_approbation
                ? $marche->date_approbation->format('d/m/Y')
                : '',
            'directeur_nom' => $directeur['nom'],
            'directeur_fonction' => $directeur['fonction'],
            'directeur_civilite' => self::formatDirecteurCivilite($directeur['nom']),
            'date_aoo' => $dateAoo,
        ];
    }

    public static function bordereau(Marche $marche): array
    {
        $doc = self::presentation($marche);
        $marche->loadMissing(['bordereauItems.lotItem']);

        $lignes = [];
        $totalHt = 0.0;

        $items = $marche->bordereauItems
            ->sortBy(fn ($row) => $row->lotItem?->numero ?? 0)
            ->values();

        foreach ($items as $bordereauItem) {
            $lotItem = $bordereauItem->lotItem;
            if (!$lotItem) {
                continue;
            }

            $quantite = (float) $lotItem->quantite;
            $puHt = (float) $bordereauItem->prix_unitaire_attributaire;
            $montantHt = round($quantite * $puHt, 2);
            $totalHt += $montantHt;

            $lignes[] = [
                'numero' => $lotItem->numero,
                'designation' => $lotItem->designation,
                'unite' => $lotItem->unite ?: '—',
                'quantite' => $quantite,
                'quantite_formate' => number_format($quantite, 2, ',', ' '),
                'prix_unitaire_ht' => $puHt,
                'prix_unitaire_formate' => number_format($puHt, 2, ',', ' '),
                'montant_ht' => $montantHt,
                'montant_ht_formate' => number_format($montantHt, 2, ',', ' '),
            ];
        }

        $totalHt = round($totalHt, 2);
        $tva = round($totalHt * 0.20, 2);
        $totalTtc = round($totalHt + $tva, 2);

        return array_merge($doc, [
            'lignes' => $lignes,
            'total_ht' => $totalHt,
            'total_ht_formate' => number_format($totalHt, 2, ',', ' '),
            'tva' => $tva,
            'tva_formate' => number_format($tva, 2, ',', ' '),
            'total_ttc' => $totalTtc,
            'total_ttc_formate' => number_format($totalTtc, 2, ',', ' '),
            'total_ttc_lettres' => MontantEnLettres::convert($totalTtc),
        ]);
    }

    public static function pageGarde(Marche $marche): array
    {
        $doc = self::presentation($marche);
        $exercice = $marche->exercice ?: self::resolveExercice($marche);

        return array_merge($doc, [
            'exercice' => $exercice,
            'type_budget' => $marche->type_budget ?: 'Investissement',
            'code_budget' => $marche->code_budget ?: '',
            'intitule_budget' => $marche->intitule_budget ?: '',
            'lot_mention' => self::resolveLotMention($marche, (string) ($doc['lot'] ?? '')),
            'pieces_dossier' => self::piecesDossierMarche(),
            'date_suivi' => '01/04/2025----30/06/2025',
            'marche_annee_label' => 'MARCHE ' . $exercice,
        ]);
    }

    public static function piecesDossierMarche(): array
    {
        return [
            ['num' => '1', 'label' => "Marché+Reçu de l'enregistrement+rapprt de présentation"],
            ['num' => '2', 'label' => 'Bordereau de prix paraphé'],
            ['num' => '3', 'label' => "P,V d'ouverture des plis"],
            ['num' => '3', 'label' => "Acte d'engagement"],
            ['num' => '4', 'label' => "Ordre de service de commencement de l'exécution"],
            ['num' => '5', 'label' => "Ordre de service de notification de l'approbation"],
            ['num' => '6', 'label' => 'Caution bancaire'],
            ['num' => '7', 'label' => "Fiche d'engagement"],
            ['num' => '1', 'label' => 'PV de la réception définitive'],
            ['num' => '2', 'label' => 'Décompte provisoire'],
            ['num' => '3', 'label' => 'Factures'],
            ['num' => '4', 'label' => 'OI'],
            ['num' => '5', 'label' => 'OP'],
            ['num' => '6', 'label' => 'OV'],
        ];
    }

    private static function resolveExercice(Marche $marche): string
    {
        if (preg_match('/\/(\d{4})\//', (string) $marche->num_marche, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\/(\d{4})\//', (string) $marche->aoo?->num_aoo, $matches)) {
            return $matches[1];
        }

        if ($marche->aoo?->date_ouverture) {
            return Carbon::parse($marche->aoo->date_ouverture)->format('Y');
        }

        return date('Y');
    }

    private static function resolveLotMention(Marche $marche, string $lotLabel = ''): string
    {
        $lotsCount = !empty($marche->aoo_id)
            ? \App\Models\Lot::query()->where('aoo_id', $marche->aoo_id)->count()
            : 0;
        $lotLabel = strtolower(trim($lotLabel));

        if ($lotsCount <= 1 || str_contains($lotLabel, 'unique')) {
            return 'lot unique';
        }

        return $lotLabel !== '' ? strtoupper($lotLabel) : 'lot unique';
    }

    public static function resolveDirecteurRegional(): array
    {
        $membre = CommissionMembre::query()
            ->where(function ($query) {
                $query->where('fonction', 'like', '%Directeur%Régional%')
                    ->orWhere('fonction', 'like', '%Directeur%Regional%')
                    ->orWhere('fonction', 'like', '%directeur%régional%')
                    ->orWhere('fonction', 'like', '%directeur%regional%');
            })
            ->orderBy('id')
            ->first();

        if (!$membre) {
            return [
                'nom' => '........................',
                'fonction' => "Directeur régional du conseil agricole de la région Rabat-Salé-Kénitra",
            ];
        }

        return [
            'nom' => $membre->nom_prenom,
            'fonction' => $membre->fonction,
        ];
    }

    private static function formatDirecteurCivilite(string $nom): string
    {
        $nom = trim($nom);
        if ($nom === '' || $nom === '........................') {
            return 'Monsieur ........................';
        }

        if (stripos($nom, 'monsieur') === 0 || stripos($nom, 'madame') === 0) {
            return $nom;
        }

        return 'Monsieur ' . $nom;
    }
}
