<?php

namespace Database\Seeders;

use App\Models\Liquidation;
use App\Models\Marche;
use App\Models\Ordonnancement;
use Illuminate\Database\Seeder;

class OrdonnancementSeeder extends Seeder
{
    public function run(): void
    {
        // Link ordonnancements to existing real liquidations
        $liq2 = Liquidation::with(['marche.fournisseur', 'marche.notificationLigne'])->find(2);

        if ($liq2 && $liq2->marche) {
            $marche = $liq2->marche;
            $fournisseur = $marche->fournisseur;
            $notifLigne = $marche->notificationLigne;

            $montantBrut = 960000.00;
            $montantHt = round($montantBrut / 1.20, 2);
            $retenueTva = round($montantBrut - $montantHt, 2);
            $retenueIas = round($montantHt * 0.05, 2);
            $netAPayer = round($montantBrut - $retenueTva - $retenueIas, 2);

            $ord = Ordonnancement::firstOrCreate(
                ['num_ordonnancement' => 'ORD-2026-001'],
                [
                    'num_op' => 'OP N° 38',
                    'exercice' => '2026',
                    'date_ordonnancement' => '2026-06-12',
                    'liquidation_id' => $liq2->id,
                    'marche_id' => $marche->id,
                    'fournisseur_id' => $fournisseur->id ?? null,
                    'notification_ligne_id' => $notifLigne->id ?? null,
                    'type_procedure' => 'Marché',
                    'reference' => $marche->num_marche,
                    'beneficiaire_nom' => $marche->titulaire ?: ($fournisseur->raison_sociale ?? 'Entreprise 1'),
                    'budget_type' => $marche->type_budget ?: 'Investissement',
                    'creance' => 'Reste à payer',
                    'code_imputation' => $marche->code_budget ?: '225320',
                    'article' => $marche->article_budget ?: '415',
                    'paragraphe' => $marche->paragraphe_budget ?: '20',
                    'ligne' => $marche->ligne_budget ?: '13',
                    'sous_ligne' => '0',
                    'intitule_depense' => $marche->objet_marche ?: 'Travaux et prestations d\'investissement',
                    'montant_brut' => $montantBrut,
                    'retenue_tva' => $retenueTva,
                    'retenue_ias' => $retenueIas,
                    'autres_retenues' => 0.00,
                    'net_a_payer' => $netAPayer,
                    'credit_consolide' => 0.00,
                    'credit_neuf' => $montantBrut,
                    'ras_total' => $retenueTva + $retenueIas,
                    'rap_total' => $netAPayer,
                    'statut' => 'À payer',
                    'observations' => 'Ordonnancement du décompte N°1 du marché ' . $marche->num_marche
                ]
            );

            if ($ord->ordres()->count() === 0) {
                $ord->ordres()->createMany([
                    [
                        'num_ordre' => 'OP N° 38',
                        'type_mouvement' => 'Paiement fournisseur',
                        'mode_paiement' => 'Virement',
                        'beneficiaire' => $ord->beneficiaire_nom,
                        'rib_compte' => $fournisseur->rib ?? '011 810 000 012 345 678 901 234',
                        'banque_agence' => $fournisseur->banque ?? 'ATTIJARIWAFA BANK - AGENCE KÉNITRA',
                        'creance' => 'Reste à payer',
                        'montant' => $netAPayer,
                        'statut' => 'À payer',
                        'observations' => 'Paiement principal net au titulaire du marché'
                    ],
                    [
                        'num_ordre' => 'OV N° 39',
                        'type_mouvement' => 'Retenue à la source TVA',
                        'mode_paiement' => 'Virement',
                        'beneficiaire' => 'TRÉSOR PUBLIC',
                        'rib_compte' => '225 330 000 706 918 851 021 328',
                        'banque_agence' => 'TRÉSORERIE PROVINCIALE DE KÉNITRA',
                        'creance' => 'Retenue à la source (TVA)',
                        'montant' => $retenueTva,
                        'statut' => 'À payer',
                        'observations' => 'Retenue à la source TVA 100%'
                    ],
                    [
                        'num_ordre' => 'OI N° 40',
                        'type_mouvement' => 'Retenue à la source IAS/IAC',
                        'mode_paiement' => 'Ordre d\'imputation',
                        'beneficiaire' => 'TRÉSOR PUBLIC',
                        'rib_compte' => '225 330 000 706 918 851 021 328',
                        'banque_agence' => 'TRÉSORERIE PROVINCIALE DE KÉNITRA',
                        'creance' => 'Retenue à la source (IAS)',
                        'montant' => $retenueIas,
                        'statut' => 'À payer',
                        'observations' => 'Retenue d\'imputation comptable IAS'
                    ],
                ]);
            }
        }
    }
}
