<?php

namespace App\Support;

use App\Models\Consultation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BonCommandeDocumentHelper
{
    public static function build(Consultation $consultation, array $documentData = [], string $montantEnLettres = '', string $documentType = ''): array
    {
        $articles = $consultation->prestations instanceof Collection && $consultation->prestations->count() > 0
            ? $consultation->prestations
            : collect(data_get($documentData, 'consistance_lignes', data_get($documentData, 'articles', [])))->map(function ($item) {
                if (is_object($item)) return $item;
                return (object) [
                    'designation' => data_get($item, 'designation', 'Prestation'),
                    'unite' => data_get($item, 'unite_mesure', data_get($item, 'unite', 'Unité')),
                    'quantite' => (float) data_get($item, 'quantite', 1),
                    'prix_unitaire_ht' => (float) data_get($item, 'prix_unitaire_ht', 0),
                    'garantie_exigee' => data_get($item, 'garantie_exigee', ''),
                ];
            });

        $totalHt = $articles->sum(fn ($p) => (float) (data_get($p, 'quantite') ?? 1) * (float) (data_get($p, 'prix_unitaire_ht') ?? 0));
        if ($totalHt == 0) {
            $totalHt = (float) ($consultation->budget->montant_estimatif_ht ?? 0);
        }

        $tvaRate = ((float) ($consultation->budget->tva ?? 20)) / 100;
        $tva = $totalHt * $tvaRate;
        $totalTtc = $totalHt + $tva;

        $isReceptionDoc = in_array($documentType, ['decision_commission_reception', 'pv_reception']) || data_get($documentData, 'type_document') === 'reception';

        if ($isReceptionDoc) {
            $rawBc = self::value($documentData, 'numero_bc', $consultation->receptionCommission->numero_bc ?? $consultation->numero_bc ?? $consultation->numero_consultation ?? '');
        } else {
            $rawBc = self::value($documentData, 'numero_bc', $consultation->numero_bc ?? $consultation->numero_consultation ?? '');
        }
        $numeroBc = preg_replace('/^BC\s+/i', '', (string) $rawBc);

        $fournisseurRetenu = self::fournisseurRetenu($consultation);
        $fournisseurs = self::fournisseurs($consultation, $documentData, $totalTtc);
        $concurrents = self::concurrents($consultation, $documentData, $totalTtc);
        $attributaire = self::value($documentData, 'attributaire', self::value($documentData, 'participant_1', data_get($concurrents, '0.nom', data_get($fournisseurs, '0.nom', 'BUREAU ALAOUI TOPO'))));
        $montantRetenu = (float) self::value($documentData, 'montant_apres_verification', self::value($documentData, 'montant_retenu', self::value($documentData, 'montant_1', data_get($concurrents, '0.montant', $totalTtc))));
        $societesInvitees = self::societesInvitees($documentData, $concurrents, $attributaire);
        $societesRefusees = self::societesRefusees($documentData, $societesInvitees, $attributaire);

        $txtLettres = $montantEnLettres ?: MontantEnLettres::convert($montantRetenu);

        if ($isReceptionDoc) {
            $numeroDecision = self::value($documentData, 'numero_decision', $consultation->receptionCommission->numero_decision ?? '');
            $typeReception = self::value($documentData, 'type_reception', $consultation->receptionCommission->type_reception ?? 'définitive');
            $rawDateDefinitive = self::value($documentData, 'date_reception_definitive', self::value($documentData, 'date_definitive', $consultation->receptionCommission->date_reception_definitive ?? null));
            $dateReceptionDefinitive = $rawDateDefinitive ? self::date($rawDateDefinitive) : '';
            $rawPeriodeDu = self::value($documentData, 'periode_du', $consultation->receptionCommission->periode_du ?? null);
            $rawPeriodeAu = self::value($documentData, 'periode_au', $consultation->receptionCommission->periode_au ?? null);
            $periodeDu = $rawPeriodeDu ? self::date($rawPeriodeDu) : '';
            $periodeAu = $rawPeriodeAu ? self::date($rawPeriodeAu) : '';
            $prestationsReceptionnees = self::value($documentData, 'prestations_receptionnees', $consultation->receptionCommission->prestations_receptionnees ?? null);
            $dateDecision = self::date(self::value($documentData, 'date_decision', self::value($documentData, 'date_document', $consultation->receptionCommission->date_decision ?? null)), Carbon::parse($consultation->date_consultation ?? now())->format('d/m/Y'));
            $dateReunionDefault = $consultation->receptionCommission->date_reunion ?? $consultation->date_limite_devis ?? now();
            $heureReunionDefault = $consultation->receptionCommission->heure_reunion ?? '10:00';
            $commissionMembers = self::receptionCommission($consultation, $documentData);
        } else {
            $typeReception = 'définitive';
            $dateReceptionDefinitive = '';
            $periodeDu = '';
            $periodeAu = '';
            $prestationsReceptionnees = null;
            $numeroDecision = self::value($documentData, 'numero_decision', $consultation->numero_decision ?? ('06/' . ($consultation->annee ?? date('Y')) . '/DRCA-RSK'));
            $dateDecision = self::date(self::value($documentData, 'date_decision'), Carbon::parse($consultation->date_consultation ?? now())->format('d/m/Y'));
            $dateReunionDefault = $consultation->date_limite_devis
                ? Carbon::parse($consultation->date_limite_devis)->format('d/m/Y')
                : ($consultation->date_reunion ? Carbon::parse($consultation->date_reunion)->format('d/m/Y') : now()->format('d/m/Y'));
            $heureReunionDefault = $consultation->heure_limite_devis ?? $consultation->heure_reunion ?? '10:00';
            $commissionMembers = self::commission($consultation, $documentData);
        }

        return [
            'data' => $documentData,
            'numero_consultation' => self::value($documentData, 'numero_consultation', $consultation->numero_consultation),
            'numero_bc' => $numeroBc ?: ($consultation->numero_consultation ?? ''),
            'objet' => self::value($documentData, 'objet', $consultation->objet_consultation),
            'lieu_execution' => self::value($documentData, 'lieu_execution', $consultation->lieu_execution ?? 'REGION DE RABAT SALE KENITRA'),
            'delai_livraison' => self::value($documentData, 'delai_livraison', ($consultation->delai_execution ?? 30) . ' jours'),
            'date_limite' => self::date(self::value($documentData, 'date_limite'), $consultation->date_limite_devis ? Carbon::parse($consultation->date_limite_devis)->format('d/m/Y') : now()->format('d/m/Y')),
            'heure_limite' => self::value($documentData, 'heure_limite', $consultation->heure_limite_devis ?? '10:00'),
            'date_document' => self::date(
                self::value($documentData, 'date_document'),
                Carbon::parse($consultation->date_consultation ?? now())->format('d/m/Y')
            ),
            'date_reunion' => self::date(self::value($documentData, 'date_reunion'), $dateReunionDefault),
            'heure_reunion' => self::value($documentData, 'heure_reunion', $heureReunionDefault),
            'heure_fin' => self::value($documentData, 'heure_fin', '11:30'),
            'articles' => $articles,
            'total_ht' => $totalHt,
            'tva_rate' => $tvaRate,
            'tva' => $tva,
            'total_ttc' => $totalTtc,
            'montant_retenu' => $montantRetenu,
            'montant_en_lettres' => $txtLettres,
            'commission' => $commissionMembers,
            'fournisseurs' => $fournisseurs,
            'concurrents' => $concurrents,
            'societes_invitees' => $societesInvitees,
            'societes_refusees' => $societesRefusees,
            'attributaire' => $attributaire,
            'societe' => self::value($documentData, 'societe', data_get($fournisseurRetenu, 'raison_sociale', 'DESTIN FLOTTE')),
            'fournisseur' => self::fournisseurRetenu($consultation),
            'titulaire_nom' => self::value($documentData, 'titulaire_nom', self::value($documentData, 'societe', data_get($fournisseurRetenu, 'raison_sociale', 'COMPTOIR COMMERCIAL DE DISTRIBUTION ET D\'EXPLOITATION'))),
            'intitule' => self::value($documentData, 'intitule', $consultation->intitule ?? $consultation->type_prestation ?? $consultation->objet_consultation ?? 'Prestation de même nature / Achat de matériel technique, de logiciels et de matériel informatique'),
            'patente' => self::value($documentData, 'patente', data_get($fournisseurRetenu, 'patente', '34255474')),
            'cnss' => self::value($documentData, 'cnss', data_get($fournisseurRetenu, 'cnss', '5614814')),
            'ice' => self::value($documentData, 'ice', data_get($fournisseurRetenu, 'ice', '001964167000010')),
            'identifiant_fiscal' => self::value($documentData, 'identifiant_fiscal', self::value($documentData, 'Identifiant Fiscale', data_get($fournisseurRetenu, 'identifiant_fiscal', '25487963'))),
            'art' => self::value($documentData, 'art', $consultation->budget->art ?? '415'),
            'par' => self::value($documentData, 'par', $consultation->budget->par ?? '30'),
            'lig' => self::value($documentData, 'lig', $consultation->budget->lig ?? '60'),
            'numero_decision' => $numeroDecision,
            'type_reception' => $typeReception,
            'date_reception_definitive' => $dateReceptionDefinitive,
            'periode_du' => $periodeDu,
            'periode_au' => $periodeAu,
            'prestations_receptionnees' => $prestationsReceptionnees,
            'date_decision' => $dateDecision,
            'numero_engagement' => self::value($documentData, 'numero_engagement', $numeroBc),
            'numero_lettre' => self::value($documentData, 'numero_lettre', '11/2024/DRCA-RSK/OS'),
            'nature_os' => self::value($documentData, 'nature_os', self::value($documentData, 'nature_ordre_service', "Notification de l'approbation")),
            'rib' => self::value($documentData, 'rib', data_get($fournisseurRetenu, 'rib', '022010000342002749835925')),
            'numero_facture' => self::value($documentData, 'numero_facture', '............'),
            'date_bon_payer' => self::date(self::value($documentData, 'date_bon_payer'), Carbon::parse($consultation->date_consultation ?? now())->format('d/m/Y')),
            'representant' => self::value($documentData, 'representant', 'Gerant'),
            'date_reception' => self::date(self::value($documentData, 'date_reception'), Carbon::parse($consultation->date_consultation ?? now())->format('d/m/Y')),
            'adresse_societe' => self::value($documentData, 'adresse_societe', data_get($fournisseurRetenu, 'adresse', data_get($fournisseurs, '0.adresse', 'Bloc J n°83 3éme étage Diour Elhomr CYM RABAT'))),
            'adresse' => self::value($documentData, 'adresse', self::value($documentData, 'adresse_societe', data_get($fournisseurRetenu, 'adresse', data_get($fournisseurs, '0.adresse', 'Bloc J n°83 3éme étage Diour Elhomr CYM RABAT')))),
            'ville_societe' => self::value($documentData, 'ville_societe', data_get($fournisseurRetenu, 'ville', data_get($fournisseurs, '0.ville', 'Rabat'))),
            'numero_avis' => self::value($documentData, 'numero_avis', $consultation->numero_consultation ?? $consultation->numero_avis ?? ''),
            'specification' => self::value($documentData, 'specification', ''),
            'designation' => self::value($documentData, 'designation', ''),
            'unite_mesure' => self::value($documentData, 'unite_mesure', ''),
            'quantite' => self::value($documentData, 'quantite', ''),
            'garantie_exigee' => self::value($documentData, 'garantie_exigee', ''),
            'numero_prix' => self::value($documentData, 'numero_prix', ''),
            'consistance_lignes' => $documentData['consistance_lignes'] ?? null,
            'delai_livraison_jours' => self::value($documentData, 'delai_livraison_jours', ''),
            'motif_ajournement' => self::value($documentData, 'motif_ajournement', 'Planification et disponibilité des agriculteurs'),
            'motif_attribution' => self::value($documentData, 'motif_attribution', "Offre la moins disante conforme"),
        ];
    }

    private static function value(array $data, string $key, mixed $default = ''): mixed
    {
        $value = data_get($data, $key);
        return $value !== null && $value !== '' ? $value : $default;
    }

    private static function date(mixed $value, string $default = ''): string
    {
        if (!$value) {
            return $default;
        }

        return Carbon::parse($value)->format('d/m/Y');
    }

    private static function commission(Consultation $consultation, array $data): array
    {
        $custom = self::value($data, 'commission', null)
            ?? self::value($data, 'membres_commission', null)
            ?? self::value($data, 'agents_commission', null);

        if (is_array($custom) && !empty($custom)) {
            return array_map(function ($item) {
                if (is_array($item)) {
                    return [
                        'nom' => $item['nom'] ?? $item['nom_prenom'] ?? '',
                        'fonction' => $item['fonction'] ?? '',
                        'qualite' => $item['qualite'] ?? 'Membre',
                    ];
                }
                return ['nom' => (string) $item, 'fonction' => '', 'qualite' => 'Membre'];
            }, $custom);
        }

        $members = is_array($consultation->membres_commission) ? $consultation->membres_commission : [];
        if (!empty($members)) {
            return $members;
        }

        return [
            ['nom' => 'AKABBABI ABDELLATIF', 'fonction' => 'Technicien de 2eme grade / Responsable des affaires juridiques et patrimoine foncier', 'qualite' => 'President'],
            ['nom' => 'HASNAA TAOUIL', 'fonction' => 'Administrateur de 1er grade / SAF', 'qualite' => 'Membre'],
            ['nom' => 'OULD ABBOU IBTISSAM', 'fonction' => 'Technicien de 2eme grade', 'qualite' => 'Membre'],
        ];
    }

    private static function receptionCommission(Consultation $consultation, array $data): array
    {
        $custom = self::value($data, 'commission', null)
            ?? self::value($data, 'membres_commission', null)
            ?? self::value($data, 'agents_commission', null);

        if (is_array($custom) && !empty($custom)) {
            return array_map(function ($item) {
                if (is_array($item)) {
                    return [
                        'nom' => $item['nom'] ?? $item['nom_prenom'] ?? '',
                        'fonction' => $item['fonction'] ?? '',
                        'qualite' => $item['qualite'] ?? 'Membre',
                    ];
                }
                return ['nom' => (string) $item, 'fonction' => '', 'qualite' => 'Membre'];
            }, $custom);
        }

        $members = is_array($consultation->receptionCommission->membres_commission ?? null)
            ? $consultation->receptionCommission->membres_commission
            : [];

        if (!empty($members)) {
            return array_map(function ($item) {
                if (is_array($item)) {
                    return [
                        'nom' => $item['nom'] ?? $item['nom_prenom'] ?? '',
                        'fonction' => $item['fonction'] ?? '',
                        'qualite' => $item['qualite'] ?? 'Membre',
                    ];
                }
                return ['nom' => (string) $item, 'fonction' => '', 'qualite' => 'Membre'];
            }, $members);
        }

        return [
            ['nom' => 'AKABBABI ABDELLATIF', 'fonction' => 'Technicien de 2eme grade / Responsable des affaires juridiques et patrimoine foncier', 'qualite' => 'President'],
            ['nom' => 'HASNAA TAOUIL', 'fonction' => 'Administrateur de 1er grade / SAF', 'qualite' => 'Membre'],
            ['nom' => 'OULD ABBOU IBTISSAM', 'fonction' => 'Technicien de 2eme grade', 'qualite' => 'Membre'],
        ];
    }

    private static function concurrents(Consultation $consultation, array $data, float $totalTtc): array
    {
        $custom = self::value($data, 'concurrents', null);
        if (is_array($custom) && !empty($custom)) {
            return array_map(function ($c) {
                return [
                    'nom' => is_array($c) ? ($c['nom'] ?? $c['raison_sociale'] ?? '') : (string) $c,
                    'montant' => is_array($c) ? (float) ($c['montant'] ?? $c['montant_ttc'] ?? 0) : 0,
                ];
            }, $custom);
        }

        $offres = $consultation->offres instanceof Collection ? $consultation->offres : collect($consultation->offres ?? []);
        $mapped = $offres->map(function ($offre) {
            return [
                'nom' => $offre->fournisseur->raison_sociale ?? '........................',
                'montant' => (float) ($offre->montant_apres_verification ?? $offre->montant_propose ?? 0),
            ];
        })->values()->all();

        if (!empty($mapped)) {
            return $mapped;
        }

        return [
            ['nom' => 'TOPOGRAPHY CONSULTING', 'montant' => 30240.00],
            ['nom' => 'LANDMAP SURVEY', 'montant' => 36360.00],
            ['nom' => 'BUREAU ALAOUI TOPO', 'montant' => 39060.00],
            ['nom' => 'BAJITOP', 'montant' => 45240.00],
            ['nom' => 'GOLDEN GEO', 'montant' => 47520.00],
        ];
    }

    private static function societesInvitees(array $data, array $concurrents, string $attributaire = ''): array
    {
        $custom = self::value($data, 'societes_invitees', null);
        if (is_array($custom) && !empty($custom)) {
            return array_map(function ($s) {
                return [
                    'nom' => is_array($s) ? ($s['nom'] ?? '') : (string) $s,
                    'montant' => is_array($s) ? (float) ($s['montant'] ?? 0) : 0,
                ];
            }, $custom);
        }

        $sorted = $concurrents;
        usort($sorted, fn ($a, $b) => ($a['montant'] <=> $b['montant']));

        // Trouver le rang de la société titulaire (attributaire)
        $attrIndex = -1;
        if (!empty($attributaire)) {
            foreach ($sorted as $idx => $c) {
                if (strcasecmp(trim($c['nom'] ?? ''), trim($attributaire)) === 0) {
                    $attrIndex = $idx;
                    break;
                }
            }
        }

        // Prendre la liste des entreprises jusqu'à l'entreprise titulaire incluse
        if ($attrIndex !== -1) {
            return array_slice($sorted, 0, $attrIndex + 1);
        }

        return array_slice($sorted, 0, min(3, count($sorted)));
    }

    private static function societesRefusees(array $data, array $societesInvitees, string $attributaire): array
    {
        $custom = self::value($data, 'societes_refusees', null);
        if (is_array($custom)) {
            return array_map(function ($s) {
                if (is_array($s)) {
                    return [
                        'nom' => $s['nom'] ?? $s['raison_sociale'] ?? '',
                        'motif' => $s['motif'] ?? 'Refus d\'invitation du maître d\'ouvrage',
                    ];
                }
                return [
                    'nom' => (string) $s,
                    'motif' => 'Refus d\'invitation du maître d\'ouvrage',
                ];
            }, $custom);
        }
        if (is_string($custom) && trim($custom) !== '') {
            $names = array_map('trim', explode(',', $custom));
            return array_map(function ($name) {
                return [
                    'nom' => $name,
                    'motif' => 'Refus d\'invitation du maître d\'ouvrage',
                ];
            }, $names);
        }

        $refused = [];
        foreach ($societesInvitees as $si) {
            $name = $si['nom'] ?? '';
            if ($name && strcasecmp($name, $attributaire) !== 0) {
                $refused[] = [
                    'nom' => $name,
                    'motif' => 'Refus d\'invitation du maître d\'ouvrage',
                ];
            }
        }
        return $refused;
    }

    private static function fournisseurs(Consultation $consultation, array $data, float $totalTtc): array
    {
        $custom = self::value($data, 'fournisseurs', null);
        if (is_array($custom)) {
            return $custom;
        }

        $offres = $consultation->offres instanceof Collection ? $consultation->offres : collect($consultation->offres ?? []);
        $mapped = $offres->map(function ($offre) {
            return [
                'nom' => $offre->fournisseur->raison_sociale ?? '........................',
                'adresse' => $offre->fournisseur->adresse ?? '',
                'ville' => $offre->fournisseur->ville ?? '',
                'montant' => $offre->montant_apres_verification ?? $offre->montant_propose ?? 0,
            ];
        })->values()->all();

        if (!empty($mapped)) {
            return $mapped;
        }

        return [
            ['nom' => '........................', 'adresse' => '........................', 'ville' => '........................', 'montant' => $totalTtc],
        ];
    }

    private static function fournisseurRetenu(Consultation $consultation): object
    {
        if ($consultation->fournisseur) {
            return $consultation->fournisseur;
        }

        if ($consultation->engagement?->fournisseur) {
            return $consultation->engagement->fournisseur;
        }

        $offre = $consultation->offres?->where('retenu', true)->first();
        if ($offre?->fournisseur) {
            return $offre->fournisseur;
        }

        return (object) [
            'raison_sociale' => 'BUREAU ALAOUI TOPO',
            'adresse' => 'APP N 6 Immeuble 01 KHENIFRA',
            'ville' => 'KHENIFRA',
            'patente' => '34255474',
            'ice' => '001964167000010',
            'cnss' => '5614814',
            'rib' => '022010000342002749835925',
        ];
    }
}
